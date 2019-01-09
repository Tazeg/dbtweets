#!/usr/bin/env node

//-----------------------------------------------------------------------------
// Web     : https://jeffprod.com
// Twitter : https://twitter.com/JeffProd
//-----------------------------------------------------------------------------
// Get tweets from SEARCH API
// https://developer.twitter.com/en/docs/tweets/search/api-reference/get-search-tweets.html
// Save each tweet with "mysave.js"
//-----------------------------------------------------------------------------

require('dotenv').config();
const Twit = require('twit');
const mysave = require('./mysave');

const SLEEP = 2000; // Rate limit APP AUTH : 450 rqt / 15' <=> 1 rqt each 2"

let t = new Twit({
    consumer_key: process.env.CONSUMER_KEY,
    consumer_secret: process.env.CONSUMER_SECRET,
    app_only_auth: true // app auth is limit 450/15', user auth is 180/15'
    });

let params = {
    q: process.env.SEARCH_REST_q,
    result_type: 'recent',
    count: 100,
    lang: process.env.SEARCH_REST_lang,
    since_id: ''
    };

let search = function() {
    console.log('Search ' + JSON.stringify(params));
    t.get('search/tweets', params, function(error, tweets) {
        if(error) {console.log(error); return;}
        let len = tweets.statuses.length;
        let i = len-1;
        while(i >=0) {
            mysave.saveTweet(tweets.statuses[i]);
            params.since_id = tweets.statuses[i].id_str; // next search after this last tweet id got
            i--;
            }
        console.log(len + ' tweets got, sleep ' + (SLEEP/1000) + 's');
        });
    }; // search()

// loop
setInterval(search, SLEEP);
