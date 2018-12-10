//-----------------------------------------------------------------------------
// Web     : https://jeffprod.com
// Twitter : https://twitter.com/JeffProd
//-----------------------------------------------------------------------------

let moment = require('moment');

let unixtime = function () {
    // Return seconds since 1970-01-01
    return new Date().getTime()/1000;
    };

let twitterDateToYMD = function(tweetDate) {
    // tweetDate = 'Fri Jun 12 11:03:22 +0000 2009'
    // OUT : '2009-06-12'
    return moment(tweetDate, 'dd MMM DD HH:mm:ss ZZ YYYY').format('YYYY-MM-DD');
    };

let twitterDateToHMS = function(tweetDate) {
    // tweetDate = 'Fri Jun 12 11:03:22 +0000 2009'
    // OUT : '11:03:22'
    return moment(tweetDate, 'dd MMM DD HH:mm:ss ZZ YYYY').format('HH:mm:ss');
    };

let twitterDateToYMDHMS = function(tweetDate) {
    // tweetDate = 'Fri Jun 12 11:03:22 +0000 2009'
    // OUT : '11:03:22'
    return moment(tweetDate, 'dd MMM DD HH:mm:ss ZZ YYYY').format('YYYY-MM-DD HH:mm:ss');
    };

exports.unixtime = unixtime;
exports.twitterDateToYMD = twitterDateToYMD;
exports.twitterDateToHMS = twitterDateToHMS;
exports.twitterDateToYMDHMS = twitterDateToYMDHMS;
