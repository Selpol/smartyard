const {existsSync, readFileSync} = require('fs')
const {join} = require("path")

class Env {
    get redisHost() {
        return this.getEnv('MQTT_REDIS_HOST')
    }

    get redisPort() {
        return this.getEnv('MQTT_REDIS_PORT')
    }

    get endpoint() {
        return this.getEnv('MQTT_ENDPOINT')
    }

    get username() {
        return this.getEnv('MQTT_USERNAME')
    }

    get password() {
        return this.getEnv('MQTT_PASSWORD')
    }

    getEnv(key, value = undefined) {
        if (Object.prototype.hasOwnProperty.call(process.env, key))
            return process.env[key]

        if (!this.env) {
            this.env = {}

            const path = join(__dirname, '..', '.env')

            if (existsSync(path)) {
                const content = readFileSync(path, 'utf-8')

                const lines = content.split('\n')

                for (const line of lines) {
                    if (line.startsWith('#') || !line.includes('='))
                        continue;

                    const index = line.indexOf('=')

                    this.env[line.substring(0, index).trim()] = line.substring(index + 1).trim()
                }
            }
        }

        if (Object.prototype.hasOwnProperty.call(this.env, key))
            return this.env[key]

        return value
    }
}

module.exports = new Env()
