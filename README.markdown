Panda client, PHP
=================

This simple PHP library provides a low-level interface to the REST API of [**Panda**](http://pandastream.com), the online video encoding service.


Usage
-----

This library requires **PHP 5.1.2** or later

Copy the `panda.php` file to your application and `require()` it. The `Panda` class implements the client, just pass your details to the constructor:

    $panda = new Panda(array(
      'api_host' => 'example.com',
      'cloud_id' => 'your-panda-cloud-id',
      'access_key' => 'your-access-key',
      'secret_key' => 'your-secret-key',
    ));

Now you can use this instance to interact with your Panda cloud. For example:

    $panda->get('videos.json')  // Retrieves a JSON listing of all your videos
    $panda->post('videos.json', array('source_url' => 'http://example.com/my-video.avi')) // Upload and encode given video
    $panda->delete('videos/12345678-90ab-cdef-1234-567890abcdef.json') // Delete a video
