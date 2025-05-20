const syslog = new (require("syslog-server"))();
const env = require("./utils/env");
const {getTimestamp} = require("./utils/getTimestamp");
const API = require("./utils/api");

const gateRabbits = [];

const motions = {};

async function motionStop(host) {
    const now = getTimestamp(new Date())

    await API.motionDetection({date: now, ip: host, motionActive: false})

    if (motions[host]) {
        await API.motion(host, motions[host].start, now)
    }

    delete motions[host]
}

syslog.on("message", async ({date, host, message}) => {
    const now = getTimestamp(date);

    /** @var {array} messages **/
    let messages = message.split("- -");

    if (message.length < 2 || !messages[1]) {
        messages = message.split(": EVENT:");

        if (message.length < 2 || !messages[1])
            return;
    }

    const isMsg = messages[1].trim();

    // Spam messages filter
    const substrings = ["STM32.DEBUG", "Вызов метода", "Тело запроса", "libre", "ddns", "DDNS", "Загружена конфигурация", "Interval", "[Server]", "Proguard start", "UART"];

    if (!isMsg || substrings.some(substring => isMsg.includes(substring)))
        return;

    console.log(`${date.toLocaleDateString()} ${date.toLocaleTimeString()} || ${host} || ${isMsg}`);

    // Send message to syslog storage
    await API.sendLog({date: now, ip: host, unit: "is", msg: isMsg});

    // Motion detection: start
    if (isMsg.includes("EVENT: Detected motion")) {
        await API.motionDetection({date: now, ip: host, motionActive: true});

        if (motions[host]) {
            clearTimeout(motions[host].interval)
        }

        motions[host] = {
            start: now,
            interval: setTimeout(motionStop, 5000, host)
        }
    }

    // Call to apartment
    if (isMsg.includes("Calling to")) {
        const match = isMsg.match(/^Calling to (\d+)(?: house (\d+))? flat/);
        if (match) {
            const house = match[2] === undefined ? 0 : match[1]; // house prefix or 0
            const flat = house > 0 ? match[2] : match[1]; // flat number from first or second position

            gateRabbits[host] = {ip: host, prefix: parseInt(house), apartmentNumber: parseInt(flat)};
        }
    }

    // Incoming DTMF for white rabbit: sending rabbit gate update
    if (isMsg.includes("Open main door by DTMF"))
        if (gateRabbits[host]) {
            const {ip, prefix, apartmentNumber} = gateRabbits[host];

            await API.setRabbitGates({date: now, ip, prefix, apartmentNumber});
        }

    // Opening door by RFID key
    if (/^Opening door by RFID [a-fA-F0-9]+, apartment \d+$/.test(isMsg)) {
        const rfid = isMsg.split("RFID")[1].split(",")[0].trim();

        await API.openDoor({date: now, ip: host, detail: rfid, by: "rfid"});
    } else if (/^Main door is opened by main reader, UUID [a-fA-F0-9]+, flat \d+$/.test(isMsg)) {
        const rfid = isMsg.split("UUID")[1].split(",")[0].trim();

        await API.openDoor({date: now, ip: host, detail: rfid, by: "rfid"});
    }

    // Opening door by personal code
    if (isMsg.includes("Opening door by code")) {
        const code = parseInt(isMsg.split("code")[1].split(",")[0]);

        await API.openDoor({date: now, ip: host, detail: code, by: "code"});
    }

    // Opening door by button pressed
    if (isMsg.includes("Main door button press"))
        await API.openDoor({date: now, ip: host, door: 0, detail: "main", by: "button"});

    // All calls are done
    if (isMsg.includes("All calls are done"))
        await API.callFinished({date: now, ip: host});
});

syslog.on("error", (err) => console.error(err.message));

syslog.start({port: env.hwIs}).then(() => console.log(`IS syslog server running on port ${env.hwIs}, with clickhouse: ${env.clickhouseHost}:${env.clickhousePort}/${env.clickhouseDatabase}`));
