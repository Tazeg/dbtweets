<?php
//-----------------------------------------------------------------------------
// Web     : https://jeffprod.com
// Twitter : https://twitter.com/JeffProd
//-----------------------------------------------------------------------------
// Get JSON tweets from Redis
// Insert into Neo4j
//-----------------------------------------------------------------------------
// USAGE
// php worker-redis.php
//---------------------------------------------------------------------------

//---------------------------------------------------------------------------
// Conf
//---------------------------------------------------------------------------

define('REDIS_SERVER', 'redis-twitter'); // refer to link in docker-compose.yml
define('NEO4J_SERVER', 'neo4j-twitter');
define('NEO4J_USER', 'neo4j');
define('NEO4J_PASSWD', '123456');


//---------------------------------------------------------------------------
// Program
//---------------------------------------------------------------------------

require 'vendor/autoload.php';

Predis\Autoloader::register();

$redis = new Predis\Client(['host' => REDIS_SERVER]);

// Wait for Neo4j to start, otherwhise we get : PHP Warning:  stream_socket_client(): unable to connect to tcp://neo4j-twitter:7687
echo 'Waiting 10s...'.PHP_EOL;
sleep(10);
$neo4j = GraphAware\Neo4j\Client\ClientBuilder::create()
    ->addConnection('bolt', 'bolt://'.NEO4J_USER.':'.NEO4J_PASSWD.'@'.NEO4J_SERVER.':7687')
    ->build();

// Create contrainst and indexes (not on screen_name that can change)
echo 'Creating Neo4j indexes...'.PHP_EOL;
$neo4j->run('CREATE CONSTRAINT ON (u:User) ASSERT u.id_str IS UNIQUE');
$neo4j->run('CREATE CONSTRAINT ON (t:Tweet) ASSERT t.id_str IS UNIQUE');
$neo4j->run('CREATE CONSTRAINT ON (h:Hashtag) ASSERT h.text IS UNIQUE');
$neo4j->run('CREATE CONSTRAINT ON (l:Link) ASSERT l.url IS UNIQUE');

define('NB_TO_GET', 100);

$done = 0;
$start = get_ms();

echo 'Worker is listening Redis...'.PHP_EOL;
while(true) {
    $tweets = $redis->lrange('tweets', 0, NB_TO_GET-1); // gettin x items
    $redis->ltrim('tweets', NB_TO_GET, -1); // and delete them
    if(empty($tweets)) {
        // empty queue, let's mini sleep
        sleep(1);
        $done = 0;
        $start = get_ms();
        continue;
        }

    $neo4j->run('BEGIN');
    foreach($tweets as $jsontweet) {
        $done++;
        addtweet_neo4j($jsontweet, $neo4j);
        } // foreach tweets
    $neo4j->run('COMMIT');

    $duration_s = (get_ms()-$start)/1000;
    $flowrate = '0';
    if($duration_s>0) {$flowrate = round($done/$duration_s, 2);}
    echo $done.' tweets inserted - '.$flowrate.' insert/s'.PHP_EOL;
    } // while(true)

/**
 * Insert a JSON tweet into Neo4j
 * @param $jsontweet : the native JSON of a tweet
 * @param $neo4j : client connect
 */
function addtweet_neo4j($jsontweet, $neo4j) {
    // (User)-[:POSTS]->(Tweet)
    // or if RT : (User)-[:RETWEETS]->(Tweet:['retweeted_status'])<-[:POSTS]-(User:['retweeted_status']['user'])
    // NB : quoting text not processed actually (is_quote_status)
    // (Tweet)-[:MENTIONS]->(User)      ['entities']['user_mentions'][x]['screen_name+name+id_str']
    // (Tweet)-[:TAGS]->(Hashtag)       ['entities']['hashtags'][x]['text']
    // (Tweet)-[:CONTAINS]->(Link)      ['entities']['urls'][x]['expanded_url']
    $jsontweet = json_decode($jsontweet, true);

    if (!empty($jsontweet['retweeted_status'])) {
        // this is a RT
        // (User:['retweeted_status']['user'])-[:POSTS]->(Tweet:['retweeted_status'])
        addtweet($jsontweet['retweeted_status']['user'], 'POSTS', $jsontweet['retweeted_status'], $neo4j);
        // (User)-[:RETWEETS]->(Tweet:['retweeted_status'])
        addtweet($jsontweet['user'], 'RETWEETS', $jsontweet['retweeted_status'], $neo4j);
        // add the RT date time
        $neo4j->run('MATCH (u:User {id_str:{user_id_str}}), (t:Tweet {id_str:{tweet_id_str}}), (u)-[r:RETWEETS]->(t) SET r.created_at_YMD={created_at_YMD}, r.created_at_HIS={created_at_HIS}', [ // edge
            'user_id_str' => $jsontweet['user']['id_str'],
            'tweet_id_str' => $jsontweet['retweeted_status']['id_str'],
            'created_at_YMD' => strdate_to($jsontweet['created_at'], 'Y-m-d'),
            'created_at_HIS' => strdate_to($jsontweet['created_at'], 'H:i:s')
            ]);
        }
    else {
        // Not a RT
        // (User)-[:POSTS]->(Tweet)
        addtweet($jsontweet['user'], 'POSTS', $jsontweet, $neo4j);
        }
    } // addtweet_neo4j

function addtweet($user, $rel, $tweet, $neo4j) {
    $place = '';
    $longitude = 0;
    $latitude = 0;
    $media_url = '';

    if(!empty($tweet['place'])) {
        $place = $tweet['place']['full_name'];
        }
    if(!empty($tweet['coordinates'])) {
        $longitude = $tweet['coordinates']['coordinates'][0];
        $latitude = $tweet['coordinates']['coordinates'][1];
        }
    if(!empty($tweet['entities']['media'])) {
        foreach($tweet['entities']['media'] as $image) {
            $media_url = $image['media_url_https']; // getting only the last one actually
            }
        }
    $source = strip_tags($tweet['source']); // ex : <a href="http://getfalcon.pro" rel="nofollow">Falcon Pro Material</a>

    // update ON MATCH to update RT count
    $q = 'MERGE (t:Tweet {id_str:{tweet_id_str}}) ON CREATE SET t+={infos_tweets} ON MATCH SET t+={infos_tweets} '.
         'MERGE (u:User {id_str:{user_id_str}}) ON CREATE SET u+={infos_user} ON MATCH SET u+={infos_user} '.
         'MERGE (u)-[:'.$rel.']->(t)';
    $args = [
        'tweet_id_str' => $tweet['id_str'],
        'infos_tweets' => [
            'id_str' => $tweet['id_str'],
            'created_at_YMD' => strdate_to($tweet['created_at'], 'Y-m-d'),
            'created_at_HIS' => strdate_to($tweet['created_at'], 'H:i:s'),
            'text' => mytrim($tweet['text']),
            'lang' => $tweet['lang'],
            'media_url' => $media_url,
            'source' => $source,
            'in_reply_to_status_id' => $tweet['in_reply_to_status_id'],
            'place' => $place,
            'longitude' => $longitude,
            'latitude' => $latitude,
            'retweet_count' => $tweet['retweet_count'],
            'favorite_count' => $tweet['favorite_count']
                ],
        'user_id_str' => $user['id_str'],
        'infos_user' => [
            'id_str' => $user['id_str'],
            'created_at_YMD' => strdate_to($user['created_at'], 'Y-m-d'),
            'created_at_HIS' => strdate_to($user['created_at'], 'H:i:s'),
            'name' => mytrim($user['name']),
            'screen_name' => strtolower($user['screen_name']),
            'location' => mytrim($user['location']),
            'url' => mytrim($user['url']),
            'description' => mytrim($user['description']),
            'protected' => $user['protected'],
            'verified' => $user['verified'],
            'followers_count' => $user['followers_count'],
            'friends_count' => $user['friends_count'],
            'listed_count' => $user['listed_count'],
            'favourites_count' => $user['favourites_count'],
            'statuses_count' => $user['statuses_count'],
            'utc_offset' => $user['utc_offset'],
            'time_zone' => $user['time_zone'],
            'geo_enabled' => $user['geo_enabled'],
            'lang' => $user['lang'],
            'profile_image_url' => $user['profile_image_url_https'],
            'profile_background_image'=> $user['profile_background_image_url_https'],
            'influence' => influence($user['friends_count'], $user['followers_count'])
            ]
        ];
    $neo4j->run($q, $args);

    // hashtags
    foreach($tweet['entities']['hashtags'] as $hashtag) {
        $h = strtolower($hashtag['text']);
        $neo4j->run('MERGE (h:Hashtag {text:{h_text}})', ['h_text' => $h]); // node
        $neo4j->run('MATCH (h:Hashtag {text:{h_text}}), (t:Tweet {id_str:{tweet_id_str}}) MERGE (t)-[:TAGS]->(h)', [  // edge
            'h_text' => $h,
            'tweet_id_str' => $tweet['id_str']
            ]);
            } // foreach hashtags

    // mentions
    foreach($tweet['entities']['user_mentions'] as $mention) {
        $neo4j->run('MERGE (u:User {id_str:{user_id_str}}) ON CREATE SET u += {infos}', [ // node
            'user_id_str' => $mention['id_str'],
            'infos' => [
                'screen_name' => strtolower($mention['screen_name']),
                'name' => mytrim($mention['name'])
            ]]);
        $neo4j->run('MATCH (u:User {id_str:{user_id_str}}), (t:Tweet {id_str:{tweet_id_str}}) MERGE (t)-[:MENTIONS]->(u)', [ // edge
            'user_id_str' => $mention['id_str'],
            'tweet_id_str' => $tweet['id_str']
            ]);
            } // foreach hashtags

    // urls : (Tweet)-[:CONTAINS]->(Link), ['entities']['urls'][x]['expanded_url']
    foreach($tweet['entities']['urls'] as $url) {
        // l'url du tweet original on s'en fout
        if(preg_match('~https://twitter.com/.+/status/[0-9]+~', $url['expanded_url'])) {continue;}
        $neo4j->run('MERGE (l:Link {url:{url}})', ['url' => $url['expanded_url']]); // node
        $neo4j->run('MATCH (l:Link {url:{url}}), (t:Tweet {id_str:{tweet_id_str}}) MERGE (t)-[:CONTAINS]->(l)', [  // edge
            'url' => $url['expanded_url'],
            'tweet_id_str' => $tweet['id_str']
            ]);
        } // foreach urls
    } // addtweet_neo4j

function mytrim($txt) {
    $txt = str_replace("\r", '', $txt);
    $txt = str_replace("\n", ' ', $txt);
    return $txt;
    }

function strdate_to($strdate, $format) {
    // IN : $strdate = 'Sat Jul 21 12:02:47 +0000 2012'
    // IN : $format = 'Y-m-d H:i:s'
    // OUT : '2012-07-21 12:02:47'
    return date($format, strtotime($strdate));
    }

function get_ms() {
    return round(microtime(true) * 1000 );
    }

function influence($friends_count, $followers_count) {
    $somme = $friends_count + $followers_count;
    if($somme>0) {return round($followers_count / $somme, 2);}
    return 0;
    }
