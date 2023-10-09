const express = require('express');
const mqtt = require('mqtt');

const app = express();

const env = require('env')

const client = mqtt.connect(env.endpoint, {username: env.username, password: env.password});
const redis = require('redis').createClient({url: 'redis://' + env.redisHost + ':' + env.redisPort});

redis.connect().then(() => {
    redis.sendCommand(['config', 'set', 'notify-keyspace-events', 'Ex']).then(() => {
        redis.subscribe('__keyevent@0__:expired', (k, e) => {
            if (e === '__keyevent@0__:expired')
                client.publish("redis/expire", JSON.stringify({key: k}));
        });
    });
});

app.post('/broadcast', express.json({type: '*/*'}), (req, res) => {
    if (req.body && req.body.topic && req.body.payload)
        client.publish(req.body.topic, JSON.stringify(req.body.payload));

    res.status(200).send("OK").end();
});

app.use(require('body-parser').urlencoded({extended: true}));

app.listen(8082);

process.on('SIGINT', () => {
    console.log("\nGracefully shutting down from SIGINT (Ctrl-C)");

    process.exit(0);
});