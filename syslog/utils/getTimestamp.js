/**
 * Convert date to timestamp
 *
 * @param {Date} date
 * @return {number}
 */
const getTimestamp = (date) => Math.floor(date.getTime() / 1000);

module.exports = {getTimestamp}
