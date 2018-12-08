//-------------------------------------------------------
// Save a JSON tweet in Redis.
// A PHP worker will insert them in Neo4j
//-------------------------------------------------------

require('dotenv').config();
const redis = require("redis");

// Push tweets to end list
// The worker will pull from the beginning of the list
r = redis.createClient({host: process.env.REDIS_SERVER});
r.on("error", function (err) {
    console.log("Error " + err);
    process.exit(1);
    });

let saveTweet = function(status) {
    // IN : status = un tweet en JSON
    r.rpush('tweets', JSON.stringify(status));
    }; // saveTweet()

exports.saveTweet = saveTweet;
