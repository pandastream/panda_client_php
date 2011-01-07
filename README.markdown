Panda client, PHP
=================

This simple PHP library provides a low-level interface to the REST API of [Panda](http://beta.pandastream.com), the online video encoding service.

You may also download the complete [example PHP application](http://github.com/newbamboo/panda_example_php) that we have made available.


Setup
-----

This library requires **PHP 5.1.2** or later

Copy the `panda.php` file to your application and `require()` it. The `Panda` class implements the client, just pass your details to the constructor:

    $panda = new Panda(array(
      'api_host' => 'api.pandastream.com', // Use api.eu.pandastream.com if your account is in the EU
      'cloud_id' => 'your-panda-cloud-id',
      'access_key' => 'your-access-key',
      'secret_key' => 'your-secret-key',
      // 'api_port' => 443, // enables https
    ));

Now you can use this instance to interact with your Panda cloud.


Basics
------

This library provides four methods, get(), post(), put() and delete(), that implement the four [REST](http://en.wikipedia.org/wiki/Representational_State_Transfer) methods. They return a JSON string as received from Panda, that you can read into an object using the PHP function json_decode(). For example:

    $ret = $panda->get('/videos.json');
    $videos = json_decode($ret);

In the example, the `$videos` variable will contain an array with details of all the videos uploaded to your system. For more information on the exposed interface, have a look at the [API documentation](http://pandastream.com/docs/api). Also see the examples below.


Example use cases
-----------------

### Listing your videos

To retrieve a list of all your videos simply do:

    $panda->get('/videos.json');

### Listing and creating encoding profiles

Before being able to encode videos, you need to define **encoding profiles**. You can retrieve a list of profiles you have defined for your account:

    $panda->get('/profiles.json');

After you sign up for Panda, you'll have one example profile created for you. This should be good for many occasions, but you can create more:

    $panda->post('/profiles.json', array(
        'title' => 'My custom profile',
        'category' => 'desktop',
        'extname' => 'mp4',
        'width' => 320,
        'height' => '240',
        'command' => 'ffmpeg -i $input_file$ -f mp4 -b 128k $resolution_and_padding$ -y $output_file',
    ));

From now on, any video that you upload will be encoded in all available profiles by default.

### Uploading a video

Let's upload a video now:

    $panda->post('/videos.json', array(
        'source_url' => 'http://example.com/path/to/video.mp4',
    ));

This POST request will also return an array with details of the video. The most important of which is the ID that Panda has assigned to the video, and will be used in all requests related to this video. It is recommended that you store this somewhere safe (like your database). See the example in more detail again:

    $res = $panda->post('/videos.json', array(
        'source_url' => 'http://example.com/path/to/video.mp4',
    ));
    $video_details = json_decode($res);
    print_r($video_details);
    # => stdClass Object
    # (
    #     [duration] =>
    #     [created_at] => 2010/06/02 10:18:21 +0000
    #     [original_filename] => video.mp4
    #     [updated_at] => 2010/06/02 10:18:21 +0000
    #     [source_url] => http://example.com/path/to/video.mp4
    #     [extname] => .mp4
    #     [id] => 620d7b41017884f4fdbc3d07c7b7d109
    #     [audio_codec] =>
    #     [file_size] =>
    #     [height] =>
    #     [fps] =>
    #     [status] => processing
    #     [video_codec] =>
    #     [width] =>
    # )

Notice the ID given to the video, which in this example is `620d7b41017884f4fdbc3d07c7b7d109`. We'll refer to it as `VIDEO_ID` in the following examples.

### Getting the status of encodings

The video will take some time to encode, depending on the size of the video and other parameters. While you wait, you can check the status of each encoding (one per profile):

    $res = $panda->get('/videos/VIDEO_ID/encodings.json');
    print_r(json_decode($res));
    # => Array
    # (
    #     [0] => stdClass Object
    #         (
    #             [created_at] => 2010/06/02 10:41:49 +0000
    #             [video_id] => VIDEO_ID
    #             [started_encoding_at] =>
    #             [updated_at] => 2010/06/02 10:41:49 +0000
    #             [encoding_progress] =>
    #             [encoding_time] =>
    #             [extname] => .mp4
    #             [id] => df073b37ff23e28c60ea19973c3268ae
    #             [file_size] =>
    #             [height] => 320
    #             [status] => processing
    #             [profile_id] => c81804595e5969d81fb30904faedd2ee
    #             [width] => 480
    #         )
    #
    #     [1] => stdClass Object
    #         (
    #             [created_at] => 2010/06/02 10:41:49 +0000
    #             [video_id] => VIDEO_ID
    #             [started_encoding_at] =>
    #             [updated_at] => 2010/06/02 10:41:49 +0000
    #             [encoding_progress] =>
    #             [encoding_time] =>
    #             [extname] => .flv
    #             [id] => 71df721ecd2171491e0b2abea18bd767
    #             [file_size] =>
    #             [height] => 240
    #             [status] => processing
    #             [profile_id] => 7248be4eeb85a09c3a7a3c4e9406ea92
    #             [width] => 320
    #         )
    #
    # )

On this example, you can see that two encodings are being generated for this video, one for each profile. Both of them are being processed at the moment, and their `status` field is marked as `processing`.

### Deleting things

After playing around a bit, you may want to clean up after your tests:

    $panda->delete('/videos/VIDEO_ID.json');
    $panda->delete('/profiles/PROFILE_ID.json');


Generating signatures
---------------------

All requests to your Panda cloud are signed using HMAC-SHA256, based on a timestamp and your Panda secret key. This is handled transparently. However, sometimes you will want to generate only this signature, in order to make a request by means other than this library. This is the case when using the JavaScript-based [panda_uploader](http://github.com/newbamboo/panda_uploader).

To do this, a method `signed_params()` is supported:

    $panda->signed_params('POST', '/videos.json');
    # => Array
    # (
    #     [cloud_id] => 'your-cloud-id'
    #     [access_key] => 8df50af4-074f-11df-b278-1231350015b1
    #     [timestamp] => 2010-02-26T15:13:18+00:00
    #     [signature] => vb/4gbjqOq/no7CRp9xN7NIZbOTXDVKhiDDKmdHKd13=
    # )

    $panda->signed_params('GET', '/videos.json', array('some_param' => 'some_value'));
    # => Array
    # (
    #     [cloud_id] => 'your-cloud-id'
    #     [some_param] => 'some_value'
    #     [access_key] => 8df50af4-074f-11df-b278-1231350015b1
    #     [timestamp] => 2010-02-26T15:13:18+00:00
    #     [signature] => jJumkCQmchWUsXFYDF0HhNoYrGkGuG2Lbe1Mkbj8cPL=
    # )
