const syslog = new (require("syslog-server"))();
const env = require("./utils/env");
const {getTimestamp} = require("./utils/getTimestamp");
const API = require("./utils/api");

const hwVer = process.argv.length === 3 && process.argv[2].split("=")[0] === '--config' ? process.argv[2].split("=")[1] : null;

const gateRabbits = [];

const motions = {};

syslog.on("message", async ({date, host, message}) => {
    const now = getTimestamp(date);
    const bwMsg = message.split("- -")[1].trim();

    // Spam messages filter
    if (
        bwMsg.indexOf("RTSP") >= 0 ||
        bwMsg.indexOf("DestroyClientSession") >= 0 ||
        bwMsg.indexOf("Request: /cgi-bin/images_cgi") >= 0 ||
        bwMsg.indexOf("GetOneVideoFrame") >= 0 ||
        bwMsg.indexOf("SS_FLASH") >= 0 ||
        bwMsg.indexOf("SS_NOIPDDNS") >= 0 ||
        bwMsg.indexOf("Have Check Param Change Beg Save") >= 0 ||
        bwMsg.indexOf("Param Change Save To Disk Finish") >= 0 ||
        bwMsg.indexOf("User Mifare CLASSIC key") >= 0 ||
        bwMsg.indexOf("Exits doWriteLoop") >= 0 ||
        bwMsg.indexOf("busybox-lib: udhcpc:") >= 0 ||
        bwMsg.indexOf("ssl_connect") >= 0 ||
        bwMsg.indexOf("ipdsConnect") >= 0
    ) {
        return;
    }

    console.log(`${date.toLocaleDateString()} ${date.toLocaleTimeString()} || ${host} || ${bwMsg}`);

    // Send message to syslog storage
    await API.sendLog({date: now, ip: host, unit: "beward", msg: bwMsg});

    // Motion detection: start
    if (bwMsg.indexOf("SS_MAINAPI_ReportAlarmHappen") >= 0) {
        await API.motionDetection({date: now, ip: host, motionActive: true});

        motions[host] = now
    }

    // Motion detection: stop
    if (bwMsg.indexOf("SS_MAINAPI_ReportAlarmFinish") >= 0) {
        await API.motionDetection({date: now, ip: host, motionActive: false});

        if (motions[host]) {
            await API.motion(host, motions[host], now)
        }
    }

    // Opening door by DTMF or CMS handset
    if (bwMsg.indexOf("Opening door by DTMF command") >= 0 || bwMsg.indexOf("Opening door by CMS handset") >= 0) {
        const apartmentNumber = parseInt(bwMsg.split("apartment")[1]);

        await API.setRabbitGates({date: now, ip: host, apartmentNumber});
    }

    // Call in gate mode with prefix: potential white rabbit
    if (bwMsg.indexOf("Redirecting CMS call to") >= 0) {
        const dst = bwMsg.split("to")[1].split("for")[0];
        gateRabbits[host] = {
            ip: host,
            prefix: parseInt(dst.substring(0, 5)),
            apartmentNumber: parseInt(dst.substring(5))
        };
    }

    // Incoming DTMF for white rabbit: sending rabbit gate update
    if (bwMsg.indexOf("Incoming DTMF RFC2833 on call") >= 0) {
        if (gateRabbits[host]) {
            const {ip, prefix, apartmentNumber} = gateRabbits[host];

            await API.setRabbitGates({date: now, ip, prefix, apartmentNumber});
        }
    }

    // Opening door by RFID key
    if (
        /^Opening door by RFID [a-fA-F0-9]+, apartment \d+$/.test(bwMsg) ||
        /^Opening door by external RFID [a-fA-F0-9]+, apartment \d+$/.test(bwMsg)
    ) {
        const rfid = bwMsg.split("RFID")[1].split(",")[0].trim();
        const door = bwMsg.indexOf("external") >= 0 ? "1" : "0";

        await API.openDoor({date: now, ip: host, door, detail: rfid, by: "rfid"});
    }

    // Opening door by RFID CLASSIC key
    if (/^RFID [a-fA-F0-9]+ is User CLASSIC key/.test(bwMsg)) {
        const segments = bwMsg.split(' ')

        await API.openDoor({date: now, ip: host, door: 0, detail: segments[1], by: "rfid"})
    }

    // Opening door by personal code
    if (bwMsg.includes("Opening door by code")) {
        const code = parseInt(bwMsg.split("code")[1].split(",")[0]);

        await API.openDoor({date: now, ip: host, detail: code, by: "code"});
    }

    // Opening door by button pressed
    if (bwMsg.indexOf("door button pressed") >= 0) {
        let door = 0;
        let detail = "main";

        if (bwMsg.indexOf("Additional") >= 0) {
            door = 1;
            detail = "second";
        }

        await API.openDoor({date: now, ip: host, door: door, detail: detail, by: "button"});
    }

    // All calls are done
    if (bwMsg.indexOf("All calls are done for apartment") >= 0) {
        const callId = parseInt(bwMsg.split("[")[1].split("]")[0]);

        await API.callFinished({date: now, ip: host, callId: callId});
    }

    // SIP call done (for DS06*)
    if (/^SIP call \d+ is DISCONNECTED.*$/.test(bwMsg) || /^EVENT:\d+:SIP call \d+ is DISCONNECTED.*$/.test(bwMsg)) {
        if (hwVer === "beward_ds") {
            await API.callFinished({date: now, ip: host});
        }
    }
});

syslog.on("error", (err) => console.error(err.message));

const port = hwVer === 'beward' ? env.hwBeward : env.hwBewardDs

syslog.start({port}).then(() => console.log(`${hwVer.toUpperCase()} syslog server running on port ${port}`));
