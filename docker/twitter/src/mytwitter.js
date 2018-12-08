let fn = require('./myfunctions');
let mysave = require('./mysave');

let tweetCount = 0;
let timeStart = fn.unixtime();
let timeLast = fn.unixtime();

let onStatusStream = function(status) {
    // Stram API : Reveiving a tweet
    // 'status' can also be : source: { limit: { track: 13, timestamp_ms: '1494706633814' } } }
    // See : https://developer.twitter.com/en/docs/tutorials/consuming-streaming-data#limit_notices
    if(status.id_str===undefined) {
        console.log(status);
        return;
        }

    // save json tweet
    mysave.saveTweet(status);
    tweetCount++;

    // Info each 5 secs
    let timeNow = fn.unixtime();
    if(timeNow-timeLast>=5) {
        console.log(tweetCount + " tweets | " + flowrate(timeNow) + " t/s");
        timeLast = timeNow;
        }
    };

let flowrate = function(timeNow) {
    return (tweetCount/(timeNow-timeStart)).toFixed(2);
    };

exports.onStatusStream = onStatusStream;
