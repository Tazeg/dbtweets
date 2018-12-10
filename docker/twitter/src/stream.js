#!/usr/bin/env node

//------------------------------------------------------------------------------
// Web     : https://jeffprod.com
// Twitter : https://twitter.com/JeffProd
//-----------------------------------------------------------------------------
// Get tweets from STREAM API
// https://developer.twitter.com/en/docs/tweets/filter-realtime/api-reference/post-statuses-filter.html
// Save each tweet with "mysave.js"
//-----------------------------------------------------------------------------

require('dotenv').config();
const Twit = require('twit');
const myTwitter = require('./mytwitter');

let stream; // to have only one stream

let t = new Twit({
    consumer_key: process.env.CONSUMER_KEY,
    consumer_secret: process.env.CONSUMER_SECRET,
    access_token: process.env.ACCESS_TOKEN,
    access_token_secret: process.env.ACCESS_TOKEN_SECRET
    });

const params = {
    track: process.env.SEARCH_STREAM_track,
    language: process.env.SEARCH_STREAM_language
    };

let startStream = function () {
    let waiting = false;
    stream = t.stream('statuses/filter', params);
    stream.on('tweet', myTwitter.onStatusStream);
    stream.on('error', function(error) {
        if(waiting) {return;} // do not start several streams
        waiting = true;
        console.log(error);
        console.log('retry within 30 secs.');
        setTimeout(startStream, 30000);
        });
    };

console.log('Start twitter stream API, params=' + JSON.stringify(params));
startStream();
