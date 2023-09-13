const fs = require('fs')

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

    get hwIs() {
        return this.getEnv('HW_IS', 'syslog://127.0.0.1:45453')
    }

    getEnv(key, value = undefined) {
        if (Object.prototype.hasOwnProperty.call(process.env, key))
            return process.env[key]

        if (!this.env) {
            this.env = {}

            if (fs.existsSync('.env')) {
                const content = fs.readFileSync('.env', 'utf-8')

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
