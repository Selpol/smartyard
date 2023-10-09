const {existsSync, readFileSync} = require('fs')
const {join} = require("path")

class Env {
    get clickhouseHost() {
        return this.getEnv('CLICKHOUSE_HOST', '127.0.0.1')
    }

    get clickhousePort() {
        return this.getEnv('CLICKHOUSE_PORT', 8123)
    }

    get clickhouseDatabase() {
        return this.getEnv('CLICKHOUSE_DATABASE', 'default')
    }

    get clickhouseUsername() {
        return this.getEnv('CLICKHOUSE_USERNAME', 'default')
    }

    get clickhousePassword() {
        return this.getEnv('CLICKHOUSE_PASSWORD', 'default')
    }

    get apiInternal() {
        return this.getEnv('API_INTERNAL', 'http://127.0.0.1/internal')
    }

    get hwBeward() {
        return this.getEnv('HW_BEWARD', 45450);
    }

    get hwBewardDs() {
        return this.getEnv('HW_BEWARD_DS', 45451);
    }

    get hwIs() {
        return this.getEnv('HW_IS', 45453)
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
