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

function log_debug(v)
    local m = ""

    if channel ~= nil then
        local l = channel.CDR("linkedid"):get()
        local u = channel.CDR("uniqueid"):get()
        local i
        if l ~= u then
            i = l .. ": " .. u
        else
            i = u
        end
        m = i .. ": "
    end

    m = m .. inspect(v)

    log.debug(m)
end

function checkin()
    local src = channel.CALLERID("num"):get()
    if src:len() == 10 then
        local prefix = tonumber(src:sub(1, 1))

        if prefix == 4 or prefix == 2 then
            log_debug("abnormal call: yes")

            app.busy()
        end
    end
end

function autoopen(flatId, domophoneId)
    if dm("autoopen", flatId) then
        log_debug("autoopen: yes")
        app.Wait(2)
        app.Answer()
        app.Wait(1)
        app.SendDTMF(dm("domophone", domophoneId).dtmf, 25, 500)
        app.Wait(1)

        return true
    end

    log_debug("autoopen: no")

    return false
end

function push(token, tokenType, platform, extension, hash, callerId, flatId, dtmf, mobile, flatNumber, domophoneId, voipEnabled)
    log_debug("sending push for: "..extension.." ["..mobile.."] ("..tokenType..", "..platform..")")

    dm("push", {
        token = token,
        tokenType = tokenType,
        platform = platform,
        extension = extension,
        hash = hash,
        callerId = callerId,
        flatId = flatId,
        dtmf = dtmf,
        mobile = mobile,
        uniq = channel.CDR("uniqueid"):get(),
        flatNumber = flatNumber,
        domophoneId = domophoneId,
        voipEnabled = voipEnabled
    })
end

function camshow(domophoneId)
    local hash = channel.HASH:get()

    if hash == nil then
        hash = md5(domophoneId .. os.time())

        channel.HASH:set(hash)

        dm("camshot", {
            domophoneId = domophoneId,
            hash = hash,
        })
    end

    return hash
end

function mobile_intercom(flatId, flatNumber, domophoneId)
    local extension
    local res = ""
    local callerId

    local subscribers = dm("subscribers", flatId)

    local dtmf = '1'

    if domophoneId >= 0 then
        dtmf = dm("domophone", domophoneId).dtmf
        if not dtmf or dtmf == '' then
            dtmf = '1'
        end
    end

    local hash = camshow(domophoneId)

    callerId = channel.CALLERID("name"):get()

    for i, s in ipairs(subscribers) do
        if s.platform ~= cjson.null and s.type ~= cjson.null and tonumber(s.voipEnabled) == 1 then
            redis:incr("autoextension")
            extension = tonumber(redis:get("autoextension"))

            if extension > 999999 then
                redis:set("autoextension", "1")
            end

            extension = extension + 2000000000

            local token = ""
            local voipEnabled = 0

            if s.voipEnabled and s.voipToken and s.voipToken ~= "" and s.voipToken ~= "off" then
                token = s.voipToken
                voipEnabled = 1
            else
                token = s.pushToken
            end

            if token ~= cjson.null and token ~= nil and token ~= "" then
                redis:setex("turn/realm/" .. config_rearm .. "/user/" .. extension .. "/key", 3 * 60, md5(extension .. ":" .. config_rearm .. ":" .. hash))
                redis:setex("mobile_extension_" .. extension, 3 * 60, hash)
                redis:setex("mobile_token_" .. extension, 3 * 60, token)

                push(token, s.tokenType, s.platform, extension, hash, callerId, flatId, dtmf, s.mobile, flatNumber, domophoneId, voipEnabled)

                res = res .. "&Local/" .. extension
            end
        end
    end

    if res ~= "" then
        return res:sub(2)
    else
        return false
    end
end

function flat_call(flatId)
    --
end

function neighbour(extension)
    --
end

extensions = {

    [ "default" ] = {

        -- calling to mobile SIP intercoms (which do not exist yet)
        [ "_2XXXXXXXXX" ] = function (context, extension)
            checkin()

            log_debug("starting loop for: " .. extension)

            local timeout = os.time() + 35

            local status = ''
            local pjsip_extension = ''
            local skip = false

            local token = redis:get("mobile_token_" .. extension)

            if token ~= "" and token ~= nil then
                channel.TOKEN:set(token)
            end

            local hash = redis:get("mobile_extension_" .. extension)

            if hash ~= "" and hash ~= nil then
                channel.HASH:set(hash)
            end

            while os.time() < timeout do
                pjsip_extension = channel.PJSIP_DIAL_CONTACTS(extension):get()

                if pjsip_extension ~= "" and pjsip_extension ~= nil then
                    if not skip then
                        log_debug("has registration: " .. extension)
                        skip = true
                    end

                    app.Dial(pjsip_extension, 35, "g")

                    status = channel.DIALSTATUS:get()

                    if status == "CHANUNAVAIL" then
                        log_debug(extension .. ': sleeping')

                        app.Wait(35)
                    end
                end
            end

            app.Hangup()
        end,

        -- call to CMS intercom
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
                            local domophone = dm("domophone", domophoneId)

                            if domophone ~= false then
                                dest = dest .. "&SIP/" .. tostring(e.apartment) .. "@" .. domophone.ip .. ":5060"
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

        -- call to IP intercom
        [ "_4XXXXXXXXX" ] = function (context, extension)
            checkin()

            log_debug("sip intercom call, dialing: " .. extension)

            local dest = channel.PJSIP_DIAL_CONTACTS(extension):get()
            if dest ~= "" and dest ~= nil then
                app.Dial(dest, 120)
            end
        end,

        -- from "PSTN" to mobile application call (for testing)
        [ "_5XXXXXXXXX" ] = function (context, extension)
            checkin()

            log_debug("mobile intercom test call")

            app.Answer()
            app.StartMusicOnHold()

            local flatId = tonumber(extension:sub(2))

            local dest = mobile_intercom(flatId, -1, -1)

            if dest ~= "" then
                log_debug("dialing: " .. dest)
                app.Dial(dest, 120, "m")
            else
                log_debug("nothing to dial")
            end
        end,

        -- panel's call
        [ "_6XXXXXXXXX" ] = function (context, extension)
            checkin()

            log_debug("intercom test call " .. string.format("1%05d", tonumber(extension:sub(2))))

            app.Dial("PJSIP/"..string.format("1%05d", tonumber(extension:sub(2))), 120, "m")
        end,

        -- static sip call
        [ "_9XXXXX" ] = function (context, extension)
            log_debug("static sip call " .. extension)

            app.Dial("PJSIP/" .. extension)
        end,

        -- SOS
        [ "SOS" ] = function ()
            local from = channel.CALLERID("num"):get()

            log_debug("sos call from: " .. inspect(from))

            local sosNumber = 112

            if from:len() == 6 and tonumber(from:sub(1, 1)) == 1 then
                local domophoneId = tonumber(from:sub(2))
                local sos = dm("sos", domophoneId)

                sosNumber = sos.sos_number

                log_debug("sos number is: " .. inspect(sosNumber))
            end

            channel.CALLERID("num"):set(string.format("%s", sosNumber))

            log_debug("sos callerid " .. inspect(channel.CALLERID("all"):get()))

            app.Dial(config_sos, 120, "m")
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

            local state = redis:get("mobile_active_" .. from)

            if state == nil then
                redis:setex("mobile_active_" .. from, 5, 1)

                log_debug("incoming ring from " .. from .. " >>> " .. extension)

                local flat

                local domophoneId = false
                local flatId = false
                local flatNumber = false

                -- is it domophone "1XXXXX"?
                if from:len() == 6 and tonumber(from:sub(1, 1)) == 1 then
                    domophoneId = tonumber(from:sub(2))

                    -- sokol's crutch
                    if extension:len() < 5 then
                        local flats = dm("apartment", { domophoneId = domophoneId, flatNumber = tonumber(extension) })

                        log_debug("bad extension, replacing from " .. extension .. " >>> ")

                        extension = string.format("1%09d", flats[1].flatId)
                    end

                    -- 1000049796, length == 10, first digit == 1 - it's a flatId
                    if extension:len() == 10 and tonumber(extension:sub(1, 1)) == 1 then
                        flatId = tonumber(extension:sub(2))
                        if flatId ~= nil then
                            log_debug("ordinal call")

                            flat = dm("flat", flatId)
                            for i, e in ipairs(flat.entrances) do
                                if flat.entrances[i].domophoneId == domophoneId then
                                    flatNumber = flat.entrances[i].apartment
                                end
                            end
                        end
                    else
                        log_debug("more than one house, has prefix")
                        flatNumber = tonumber(extension:sub(5))
                        if flatNumber ~= nil then
                            local flats = dm("flatIdByPrefix", {
                                domophoneId = domophoneId,
                                flatNumber = flatNumber,
                                prefix = tonumber(extension:sub(1, 4)),
                            })
                            if #flats == 1 then
                                flat = flats[1]
                                flatId = flat.flatId
                            end
                        end
                    end
                end

                log_debug("domophoneId: " .. inspect(domophoneId))
                log_debug("flatId: " .. inspect(flatId))
                log_debug("flatNumber: " .. inspect(flatNumber))

                if domophoneId and flatId and flatNumber then
                    log_debug("incoming ring from ip panel #" .. domophoneId .. " -> " .. flatId .. " (" .. flatNumber .. ")")

                    local entrance = dm("entrance", domophoneId)
                    log_debug("entrance: " .. inspect(entrance))

                    channel.CALLERID("name"):set(entrance.callerId .. ", " .. math.floor(flatNumber))

                    if not autoopen(flatId, domophoneId) then
                        local dest = ""

                        local cmsConnected = false
                        local hasCms = false

                        for i, e in ipairs(flat.entrances) do
                            if e.domophoneId == domophoneId and e.matrix >= 1 then
                                cmsConnected = true
                            end
                            if e.matrix >= 1 then
                                hasCms = true
                            end
                        end

                        log_debug("cmsConnected: " .. inspect(cmsConnected) .. ", hasCms: " .. inspect(hasCms))

                        if not cmsConnected and hasCms then
                            dest = dest .. "&Local/" .. string.format("3%09d", flatId)
                        end

                        -- application(s) (mobile intercom(s))
                        local mi = mobile_intercom(flatId, flatNumber, domophoneId)
                        if mi then
                            dest = dest .. "&" .. mi
                        end

                        -- SIP intercom(s)
                        local sip_intercom = channel.PJSIP_DIAL_CONTACTS(string.format("4%09d", flatId)):get()
                        if sip_intercom ~= "" and sip_intercom ~= nil then
                            dest = dest .. "&Local/" .. string.format("4%09d", flatId)
                        end

                        if dest:sub(1, 1) == '&' then
                            dest = dest:sub(2)
                        end

                        if dest ~= "" then
                            log_debug("dialing: " .. dest)
                            app.Dial(dest, 120)
                        else
                            log_debug("nothing to dial")
                        end
                    end
                else
                    log_debug("something wrong, going out")
                end

                app.Hangup()
            end
        end,

        [ "h" ] = function (context, extension)
            local src = channel.CDR("src"):get()
            local status = channel.DIALSTATUS:get()

            if status == nil then
                status = "UNKNOWN"
            end

            local hash = channel.HASH:get()

            if hash == nil then
                hash = "none"
            end

            local token = channel.TOKEN:get()

            if token == nil then
                token = "none"
            end

            log_debug("call ended: " .. src .. " >>> " .. channel.CDR("dst"):get() .. ", channel status: " .. status .. ", hash: " .. hash .. ", token: " .. token)
        end,
    },
}
