package.path = "/etc/asterisk/?.lua;./live/etc/asterisk/?.lua;/etc/asterisk/lua/?.lua;./live/etc/asterisk/lua/?.lua;./lua/?.lua;" .. package.path
package.cpath = "/usr/share/lua/5.3/?.so;" .. package.cpath

log = require "log"
inspect = require "inspect"
http = require "socket.http"
ltn12 = require "ltn12"
cjson = require "cjson"
md5 = (require 'md5').sumhexa
redis = require "redis"

require "config"

redis = redis.connect({
    host = redis_server_host,
    port = redis_server_port
})

if redis_server_auth and redis_server_auth ~= nil then
    redis:auth(redis_server_user, redis_server_auth)
end

function log_debug(v)
    log.debug(inspect(v))
end

function dm(action, request)
    local body = {}

    if not request then
        request = ""
    end

    local r = cjson.encode(request)

    http.request {
        method = "POST",
        url = config_server .. "/" .. action,
        source = ltn12.source.string(r),
        headers = {
            ["content-type"] = "application/json",
            ["content-length"] = r:len()
        },
        sink = ltn12.sink.table(body)
    }

    response = table.concat(body)

    result = false

    if response ~= "" then
        pcall(function ()
            result = cjson.decode(response)
        end)
    end

    return result
end

function checkin()
    local src = channel.CALLERID("num"):get()

    if src:len() == 10 then
        local prefix = tonumber(src:sub(1, 1))

        if prefix == 4 or prefix == 2 then
            app.Busy()
        end
    end
end

function send(domophone_id, flat_id, flat_number, caller_id, subscribers)
    local exts = {}
    local extension
    local res = ""

    local hash = channel.HASH:get()

    if hash == nil then
        hash = md5(domophone_id .. os.time())

        channel.HASH:set(hash)
    end

    redis:setex("call/hash/" .. hash, 3 * 60, cjson.encode({ domophone = domophone_id }))

    for i, id in ipairs(subscribers) do
        redis:incr("autoextension")
        extension = tonumber(redis:get("autoextension"))

        if extension > 999999 then
            redis:set("autoextension", "1")
        end

        extension = extension + 2000000000

        res = res .. "&Local/" .. extension

        exts[tostring(math.floor(id))] = extension

        redis:setex("call/user/" .. extension, 3 * 60, cjson.encode({ hash = hash, domophone = domophone_id, subscriber = id }))
    end

    local request = {hash = hash, domophone_id = domophone_id, flat_id = flat_id, flat_number = flat_number, caller_id = caller_id, subscribers = subscribers, extensions = exts}
    local response = dm("send", request)

    if response.success == false then
        return false
    end

    if res ~= "" then
        return res:sub(2)
    else
        return false
    end
end

extensions = {

    [ "default" ] = {
        -- Звонок в сторону клиента
        [ "_2XXXXXXXXX" ] = function (context, extension)
            checkin()

            local timeout = os.time() + 35

            local status = ''
            local pjsip_extension = ''
            local skip = false

            local call = cjson.decode(redis:get("call/user/" .. extension))

            if call ~= cjson.null and call ~= nil then
                channel.HASH:set(call.hash)
                channel.SUBSCRIBER:set(call.subscriber)
            end

            while os.time() < timeout do
                pjsip_extension = channel.PJSIP_DIAL_CONTACTS(extension):get()

                if pjsip_extension ~= "" and pjsip_extension ~= nil then
                    if not skip then
                        log_debug("[Домофон/" .. math.floor(call.domophone) .. "] Найдена регистрация для " .. extension .. ", абонента " .. call.subscriber)

                        skip = true
                    end

                    app.Dial(pjsip_extension, 35, "g")

                    status = channel.DIALSTATUS:get()

                    if status == "CHANUNAVAIL" then
                        log_debug("[Домофон/" .. math.floor(call.domophone) .. "] Приостановка потока для " .. extension .. ", абонента " .. call.subscriber)

                        app.Wait(35)
                    end
                else
                    app.Wait(0.25)
                end
            end

            app.Hangup()
        end,

        -- Звонок в сторону домофона
        [ "_3XXXXXXXXX" ] = function (context, extension)
            checkin()

            local flatId = tonumber(extension:sub(2))
            local flat = dm("flat", flatId)

            if flat then
                log_debug("flat intercom call for flat: id " .. tostring(flatId) .. " , number " .. tostring(flat.flat))

                local dest = ""

                for i, e in ipairs(flat.entrances) do
                    if e.apartment > 0 and e.domophoneId > 0 and e.matrix > 0 then
                        local trunk = string.format("1%05d", e.domophoneId)
                        local contact = channel.PJSIP_DIAL_CONTACTS(trunk):get()

                        if contact ~= nil and contact ~= "" then
                            log_debug("[_3XXXXXXXXX] trunk: " .. trunk .. ", contact: " .. contact)

                            local contact = contact:sub(18)
                            local index = string.find(contact, "@", 1, true)

                            dest = dest .. "&PJSIP/" .. trunk .. "/sip:" .. string.format("%d", e.apartment) .. contact:sub(index)
                        else
                            local domophone = dm("domophone", e.domophoneId)

                            if domophone ~= false then
                                dest = dest .. "&PJSIP/" .. tostring(math.floor(e.apartment)) .. "@" .. domophone.ip .. ":5060"
                            end
                        end
                    end
                end

                if dest ~= "" then
                    dest = dest:sub(2)

		            log_debug("[_3XXXXXXXXX] dest: " .. dest)

                    app.Dial(dest, 120)
                end
            end

            app.Hangup()
        end,

        -- Звонок в сторону IP-панели
        [ "_4XXXXXXXXX" ] = function (context, extension)
            checkin()

            local from = channel.CALLERID("num"):get()

            local dest = channel.PJSIP_DIAL_CONTACTS(extension):get()

            log_debug("SIP звонок с устройства " .. from .. " на " .. dest)

            if dest ~= "" and dest ~= nil then
                app.Dial(dest, 120)
            end
        end,

        -- Тестовый звонок в сторону клиента
        [ "_5XXXXXXXXX" ] = function (context, extension)
            checkin()

            local from = channel.CALLERID("num"):get()

            app.Answer()
            app.StartMusicOnHold()

            local domophoneId = tonumber(from:sub(2))

            -- Получаем информацию для проведения звонка
            local response = dm("call", { domophone_id = domophoneId, extension = extension })

            if response.success then
                -- Устанавливаем имя звонящего
                channel.CALLERID("name"):set(response.data.caller)

                local dest = ""

                local mi = send(domophoneId, response.data.flat_id, response.data.flat_number, response.data.caller, response.data.subscribers)

                if mi then
                    dest = dest .. "&" .. mi
                end

                if dest:sub(1, 1) == '&' then
                    dest = dest:sub(2)
                end

                if dest ~= "" then
                    log_debug("[Домофон/" .. domophoneId .. "] Начат тестовый звонок на " .. dest)
                    app.Dial(dest, 120)
                end
            end
        end,

        -- panel's call
        [ "_6XXXXXXXXX" ] = function (context, extension)
            checkin()

            local from = channel.CALLERID("num"):get()

            log_debug("Панельный звонок с устройства " .. from .. " на " .. string.format("1%05d", tonumber(extension:sub(2))))

            app.Dial("PJSIP/"..string.format("1%05d", tonumber(extension:sub(2))), 120, "m")
        end,

        -- static sip call
        [ "_9XXXXX" ] = function (context, extension)
            local from = channel.CALLERID("num"):get()

            log_debug("Статический звонок с устройства " .. from .. " на " .. extension)

            app.Dial("PJSIP/" .. extension)
        end,

        -- SOS
        [ "SOS" ] = function ()
            local from = channel.CALLERID("num"):get()

            local sosNumber = 112

            if from:len() == 6 and tonumber(from:sub(1, 1)) == 1 then
                local domophoneId = tonumber(from:sub(2))
                local sos = dm("sos", domophoneId)

                sosNumber = sos.sos_number

                log_debug("[Домофон/" .. domophoneId .. "] SOS на " .. sosNumber)
            end

            channel.CALLERID("num"):set(string.format("%s", sosNumber))

            app.Dial("PJSIP/112@pbx.icomtel.info", 120, "m")
            app.Hangup()
        end,

        -- consierge
        [ "9999" ] = function ()
            checkin()

            log_debug(channel.CALLERID("num"):get().." >>> 9999")

            app.Answer()
            app.StartMusicOnHold()
            app.Wait(900)
        end,

        -- all others
        [ "_X!" ] = function (context, extension)
            checkin()

            local from = channel.CALLERID("num"):get()

            local state = redis:get("call/active/" .. from)

            -- Защита от дубликатов звонка
            if state == nil then
                redis:setex("call/active/" .. from, 5, 1)

                local domophoneId = tonumber(from:sub(2))

                -- Получаем информацию для проведения звонка
                local response = dm("call", { domophone_id = domophoneId, extension = extension })

                if response.success then
                    -- Проверка на автооткрытие
                    if response.data.auto_open then
                        log_debug("[Домофон/" .. domophoneId .. "] Автооткрытие")

                        app.Wait(2)
                        app.Answer()
                        app.Wait(1)

                        if response.data.dtmf then
                            app.SendDTMF(response.data.dtmf, 25, 500)
                        else
                            app.SendDTMF("1", 25, 500)
                        end
                    else
                        -- Устанавливаем имя звонящего
                        channel.CALLERID("name"):set(response.data.caller)

                        local dest = ""

                        -- Делать ли звонок на КМС домофона, если на домофоне нету КМС и нашли другой домофон с ним
                        if response.data.call_cms then
                            dest = dest .. "&Local/" .. string.format("3%09d", response.data.flat_id)
                        end

                        local mi = send(domophoneId, response.data.flat_id, response.data.flat_number, response.data.caller, response.data.subscribers)

                        if mi then
                            dest = dest .. "&" .. mi
                        end

                        -- Звоним в SIP интерком, если нужно и он зарегестрирован
                        if response.data.call_sip then
                            local sip_intercom = channel.PJSIP_DIAL_CONTACTS(string.format("4%09d", response.data.flat_id)):get()

                            if sip_intercom ~= "" and sip_intercom ~= nil then
                                dest = dest .. "&Local/" .. string.format("4%09d", response.data.flat_id)
                            end
                        end

                        if dest:sub(1, 1) == '&' then
                            dest = dest:sub(2)
                        end

                        if dest ~= "" then
                            log_debug("[Домофон/" .. domophoneId .. "] Начат звонок на " .. dest)

                            app.Dial(dest, 120)
                        end
                    end
                end

                app.Hangup()
            end
        end,

        [ "h" ] = function (context, extension)
            local src = channel.CDR("src"):get()
            local status = channel.DIALSTATUS:get()

            if status == nil then
                status = "неизвестно"
            end

            local subscriber = channel.SUBSCRIBER:get()

            if subscriber == nil then
                subscriber = "Отсуствует"
            end

            local message = ""

            if src:sub(1, 1) == "1" then
                message = "[Домофон/" .. tonumber(src:sub(2)) .. "] Звонок"
            else
                message = "Звонок с устройства " .. src
            end

            log_debug(message .. " на " .. channel.CDR("dst"):get() .. " завершен со статусом " .. status .. ", абонент " .. subscriber)
        end,
    },
}
