<?php

require_once(ABSPATH . 'wp-includes/media.php');

class Filter_Rokka_Upload
{
    function __construct()
    {
        $this->init_filters();
    }

    protected function init_filters()
    {
        //add_filter('get_attached_file', 'rokka_get_attached_file');
        add_filter('attachment_fields_to_save', array($this, 'rokka_attachment_fields_to_save'));
//add_filter('wp_handle_upload', 'rokka_wp_handle_upload', 10, 2); //todo uncomment
        add_filter('wp_update_attachment_metadata', array($this, 'rokka_upload_attachment_metadata'), 1, 2);


// Rewriting URLs, doesn't depend on plugin being setup

        /*
        add_filter( 'wp_get_attachment_url', array( $this, 'wp_get_attachment_url' ), 99, 2 );
        add_filter( 'get_image_tag', array( $this, 'maybe_encode_get_image_tag' ), 99, 6 );
        add_filter( 'wp_get_attachment_image_src', array( $this, 'maybe_encode_wp_get_attachment_image_src' ), 99, 4 );
        add_filter( 'wp_prepare_attachment_for_js', array( $this, 'maybe_encode_wp_prepare_attachment_for_js' ), 99, 3 );
        add_filter( 'image_get_intermediate_size', array( $this, 'maybe_encode_image_get_intermediate_size' ), 99, 3 );
        add_filter( 'get_attached_file', array( $this, 'get_attached_file' ), 10, 2 );
        */
        add_filter('get_attached_file', array($this, 'rokka_get_attached_file'));

        //add_filter('wp_prepare_attachment_for_js', array($this, 'rokka_prepare_image_for_js'));

        add_filter('wp_get_attachment_image_src', array($this, 'rokka_get_attachment_image_src'), 1, 4);

        add_filter('wp_get_attachment_url', array($this, 'rokka_get_attachment_url'));

        add_filter('set_url_scheme', array($this, 'rokka_set_url_scheme'), 1, 3);

        //rewrite the image URL's in metadata in order to fetch the image from Rokka
        add_filter('wp_get_attachment_metadata', array($this, 'rokka_get_attachment_metadata'), 1, 3);

        add_filter('wp_save_image_editor_file', array($this, 'rokka_save_image_editor_file'), 1, 3);

    }


    /**
     * Filter whether to skip saving the image file.
     *
     * Returning a non-null value will short-circuit the save method,
     * returning that value instead.
     *
     * @since 3.5.0
     *
     * @param mixed $override Value to return instead of saving. Default null.
     * @param string $filename Name of the file to be saved.
     * @param WP_Image_Editor $image WP_Image_Editor instance.
     * @param string $mime_type Image mime type.
     * @param int $post_id Post ID.
     */
    function rokka_save_image_editor_file($override, $filename, $image, $mime_type, $post_id)
    {
        //file_put_contents("/tmp/wordpress.log", __METHOD__ . print_r($filename,true) . PHP_EOL, FILE_APPEND);

    }


    /**
     * @param $data
     * @param $post_id
     * @return mixed
     */
    function rokka_get_attachment_metadata($data, $post_id)
    {
        //file_put_contents("/tmp/wordpress.log", __METHOD__ . print_r($data, true) . PHP_EOL, FILE_APPEND);

        return $data;

    }


    /**
     * @param $url
     * @param $scheme
     * @param $orig_scheme
     * @return mixed
     */
    function rokka_set_url_scheme($url, $scheme, $orig_scheme)
    {

        if (strpos($url, '.rokka.io')) {

            $scheme = 'https';
            $url = preg_replace('#^\w+://#', $scheme . '://', $url);
        }

        return $url;

    }


    /**
     * @return array
     */
    function list_thumbnail_sizes()
    {
        global $_wp_additional_image_sizes;
        $sizes = array();
        $rSizes = array();
        foreach (get_intermediate_image_sizes() as $s) {
            $sizes[$s] = array(0, 0);
            if (in_array($s, array('thumbnail', 'medium', 'large'))) {
                $sizes[$s][0] = get_option($s . '_size_w');
                $sizes[$s][1] = get_option($s . '_size_h');
            } else {
                if (isset($_wp_additional_image_sizes) && isset($_wp_additional_image_sizes[$s])) {
                    $sizes[$s] = array(
                        $_wp_additional_image_sizes[$s]['width'],
                        $_wp_additional_image_sizes[$s]['height'],
                    );
                }
            }
        }

        foreach ($sizes as $size => $atts) {
            $rSizes[$size] = $atts;
        }

        return $rSizes;
    }


    /**
     * @param $url
     * @return mixed
     */
    function rokka_get_attachment_url($url)
    {
        //todo remove
        file_put_contents("/tmp/wordpress.log", 'rokka_get_attachment_url: ' . print_r($url,true) . PHP_EOL, FILE_APPEND);

        return $url;
    }

    /**
     * Return the Rokka URL
     * unless we know the calling process is and we are happy
     * to copy the file back to the server to be used
     *
     * @param string $file
     * @param int $attachment_id
     *
     * @return string
     */
    function rokka_get_attached_file($file)
    {

        //file_put_contents( "/tmp/wordpress.log", 'rokka_get_attached_file: ' . print_r( $file, true ) . PHP_EOL, FILE_APPEND );

        /*
        if ( file_exists( $file ) || ! ( $s3object = $this->is_attachment_served_by_s3( $attachment_id ) ) ) {
            return $file;
        }

        $url = $this->get_attachment_url( $attachment_id );

        // return the URL by default
        $file = apply_filters( 'as3cf_get_attached_file', $url, $file, $attachment_id, $s3object );
    */

        return $file;
    }

    /**
     * @param $data
     * @param $post_id
     * @return array|mixed|WP_Error
     */
    function rokka_upload_attachment_metadata($data, $post_id)
    {
        // die(var_dump($data));

        //  die(var_dump(get_attachment_file_paths( $post_id, true, $data )));
        //todo do all the stuff here instead of rokka_wp_handle_upload

        //the meta stuff should be possible here too
        $file_path = get_attached_file($post_id, true);
        $file_name = basename($file_path);
        $type = get_post_mime_type($post_id);
        $allowed_types = $this->rokka_get_allowed_mime_types();

        // check mime type of file is in allowed S3 mime types
        if (!in_array($type, $allowed_types)) {
            $error_msg = sprintf(__('Mime type %s is not allowed in rokka', 'rokka-image-cdn'), $type);
            //todo implement
            return return_upload_error($error_msg, $return_metadata);
        }


        // Check file exists locally before attempting upload
        if (!file_exists($file_path)) {
            $error_msg = sprintf(__('File %s does not exist', 'rokka-image-cdn'), $file_path);

            return return_upload_error($error_msg, $return_metadata);
        }

        $data = $this->rokka_upload($post_id, $data);

        return $data;

        // add_post_meta( $post_id, 'rokka', $s3object );
        /*
         *array (size=5)
      'width' => int 512
      'height' => int 288
      'file' => string '2016/12/6533_025-1.png' (length=22)
      'sizes' =>
        array (size=2)
          'thumbnail' =>
            array (size=4)
              'file' => string '6533_025-1-150x150.png' (length=22)
              'width' => int 150
              'height' => int 150
              'mime-type' => string 'image/png' (length=9)
          'medium' =>
            array (size=4)
              'file' => string '6533_025-1-350x197.png' (length=22)
              'width' => int 350
              'height' => int 197
              'mime-type' => string 'image/png' (length=9)
      'image_meta' =>
        array (size=12)
          'aperture' => string '0' (length=1)
          'credit' => string '' (length=0)
          'camera' => string '' (length=0)
          'caption' => string '' (length=0)
          'created_timestamp' => string '0' (length=1)
          'copyright' => string '' (length=0)
          'focal_length' => string '0' (length=1)
          'iso' => string '0' (length=1)
          'shutter_speed' => string '0' (length=1)
          'title' => string '' (length=0)
          'orientation' => string '0' (length=1)
          'keywords' =>
            array (size=0)
              empty
         *
         *
         */


    }

    /**
     * @return array
     */
    function rokka_get_allowed_mime_types()
    {
        //todo complete list with allowed mime types

        return ['image/jpeg', 'image/tiff', 'image/png'];
    }


    function rokka_get_client()
    {
        return \Rokka\Client\Factory::getImageClient(get_option('rokka_company_name'), get_option('rokka_api_key'), get_option('rokka_api_secret'));
    }
    /**
     * @param $post_id
     * @param $data
     * @return mixed
     */
    function rokka_upload($post_id, $data)
    {
        $file_paths = $this->get_attachment_file_paths($post_id, true, $data);

        //todo allow wp to remove file from local filesystem
        //$remove_local_files_setting = get_setting( 'remove-local-file' );
        $remove_local_files_setting = false;

        //$client = \Rokka\Client\Factory::getImageClient(get_option('rokka_company_name'), get_option('rokka_api_key'), get_option('rokka_api_secret'));
        $client = $this->rokka_get_client();

        $fileParts = explode('/', $file_paths['full']);

        $fileName = array_pop($fileParts);

        $sourceImage = $client->uploadSourceImage(file_get_contents($file_paths['full']), $fileName);

        $sourceImages = $sourceImage->getSourceImages();
        $sourceImage = array_pop($sourceImages);
        var_dump($sourceImages); //todo remove

        $url = 'https://api.rokka.io' . $sourceImage->link . '.' . $sourceImage->format;

        //todo check if stack exists for given dimension


        $rokka_info = array(
            'url' => $url,
            'hash' => $sourceImage->hash,
            'format' => $sourceImage->format,
            'organization' => $sourceImage->organization,
            'link' => $sourceImage->link,
            'local_files_removed' => $file_paths,
            'created' => $sourceImage->created,
        );

        //var_dump( $rokka_info );

        //todo allenfalls stacks in array integrieren.
        update_post_meta($post_id, 'rokka_info', $rokka_info);


        if ($remove_local_files_setting) {
            // Remove duplicates
            $files_to_remove = array_unique($files_to_remove);
            // Delete the files
            //todo implement this if you know how to do it
            remove_local_files($files_to_remove);
        }
        /* //todo source image
         * object(Rokka\Client\Core\SourceImageCollection)[379]
      private 'sourceImages' =>
        array (size=1)
          0 =>
            object(Rokka\Client\Core\SourceImage)[381]
              public 'organization' => string 'liip-development' (length=16)
              public 'binaryHash' => string '9140ff87e46da48b0a13e6538c1e9e4d31a28144' (length=40)
              public 'hash' => string 'b8bd589861c7fd6656515b60376bddc11b9caa5d' (length=40)
              public 'name' => string '2014-03-05-18.41.28-1.jpg' (length=25)
              public 'format' => string 'jpg' (length=3)
              public 'size' => int 875998
              public 'width' => int 1520
              public 'height' => int 2688
              public 'staticMetadata' =>
                array (size=0)
                  empty
              public 'dynamicMetadata' =>
                array (size=0)
                  empty
              public 'created' =>
                object(DateTime)[385]
                  public 'date' => string '2016-12-08 10:08:01.000000' (length=26)
                  public 'timezone_type' => int 1
                  public 'timezone' => string '+00:00' (length=6)
              public 'link' => string '/sourceimages/liip-development/b8bd589861c7fd6656515b60376bddc11b9caa5d' (length=71)
    63
         *
         */
        return $data;

    }


    /**
     * Remove files from the local site
     *
     * @param array $file_paths array of files to remove
     */
    function remove_local_files($file_paths)
    {
        foreach ($file_paths as $path) {

            if (!@unlink($path)) {
                return new WP_Error('Error removing local file ' . $path);
            }
        }
    }

    /**
     * Helper to return meta data on upload error
     *
     * @param string $error_msg
     * @param array|null $return
     *
     * @return array|WP_Error
     */
    function return_upload_error($error_msg, $return = null)
    {
        if (is_null($return)) {
            return new WP_Error('exception', $error_msg);
        }

        return $return;
    }


    /**
     * Get file paths for all attachment versions.
     *
     * @param int $attachment_id
     * @param bool $exists_locally
     * @param array|bool $meta
     * @param bool $include_backups
     *
     * @return array
     */
    function get_attachment_file_paths($attachment_id, $exists_locally = true, $meta = false, $include_backups = true)
    {
        $paths = array();
        $file_path = get_attached_file($attachment_id, true);
        $file_name = basename($file_path);
        $backups = get_post_meta($attachment_id, '_wp_attachment_backup_sizes', true);

        if (!$meta) {
            $meta = get_post_meta($attachment_id, '_wp_attachment_metadata', true);
        }

        if (is_wp_error($meta)) {
            return $paths;
        }

        $original_file = $file_path; // Not all attachments will have meta

        if (isset($meta['file'])) {
            $original_file = str_replace($file_name, basename($meta['file']), $file_path);
        }

        // Original file
        $paths['full'] = $original_file;

        // Sizes
        if (isset($meta['sizes'])) {
            foreach ($meta['sizes'] as $size => $file) {
                if (isset($file['file'])) {
                    $paths[$size] = str_replace($file_name, $file['file'], $file_path);
                }
            }
        }

        // Thumb
        if (isset($meta['thumb'])) {
            $paths[] = str_replace($file_name, $meta['thumb'], $file_path);
        }

        // Backups
        if ($include_backups && is_array($backups)) {
            foreach ($backups as $backup) {
                $paths[] = str_replace($file_name, $backup['file'], $file_path);
            }
        }

        // Allow other processes to add files to be uploaded
        $paths = apply_filters('rokka_attachment_file_paths', $paths, $attachment_id, $meta);

        // Remove duplicates
        $paths = array_unique($paths);

        // Remove paths that don't exist
        if ($exists_locally) {
            foreach ($paths as $key => $path) {
                if (!file_exists($path)) {
                    unset($paths[$key]);
                }
            }
        }

        return $paths;
    }

    /**
     * Retrieve an image to represent an attachment.
     *
     * A mime icon for files, thumbnail or intermediate size for images.
     *
     * The returned array contains four values: the URL of the attachment image src,
     * the width of the image file, the height of the image file, and a boolean
     * representing whether the returned array describes an intermediate (generated)
     * image size or the original, full-sized upload.
     *
     *
     * @param int $attachment_id Image attachment ID.
     * @param string|array $size Optional. Image size. Accepts any valid image size, or an array of width
     *                                    and height values in pixels (in that order). Default 'thumbnail'.
     * @param bool $icon Optional. Whether the image should be treated as an icon. Default false.
     *
     * @return false|array Returns an array (url, width, height, is_intermediate), or false, if no image is available.
     */
    function rokka_get_attachment_image_src($image, $attachment_id, $size = 'thumbnail', $icon = false)
    {

        $rokka_data = get_post_meta($attachment_id, 'rokka_info', true);
        $image_data = get_post_meta($attachment_id, '_wp_attachment_metadata', true);
        //todo use sizes for stackname, also figure out how to deal with rotation and stuff.
        $sizes = $image_data['sizes'];
        /* example
        [sizes] => Array
            (
                [thumbnail] => Array
                    (
                        [file] => Lee-e1483459760152-150x150.png
                        [width] => 150
                        [height] => 150
                        [mime-type] => image/png
                    )

                [medium] => Array
                    (
                        [file] => Lee-e1483459760152-310x350.png
                        [width] => 310
                        [height] => 350
                        [mime-type] => image/png
                    )

                [post-thumbnail] => Array
                    (
                        [file] => Lee-512x510.png
                        [width] => 512
                        [height] => 510
                        [mime-type] => image/png
                    )

            )

        */

        if ($rokka_data) {

            $sizes = $this->list_thumbnail_sizes();
            $sizeString = null;
            if (is_array($size)) {
                $imageSizes = $size;
                foreach ($sizes as $size_name => $sizes_values) {

                    if ($sizes_values[0] == $size[0]) {
                        $sizeString = $size_name;
                        continue;
                    }
                }
                if (is_null($sizeString)) {
                    $sizeString = 'thumbnail';
                }

            } else {
                $imageSizes = $sizes[$size];
                $sizeString = $size;
            }

            $url = 'https://' . $rokka_data['organization'] . '.rokka.io/' . $sizeString . '/' . $rokka_data['hash'] . '.' . $rokka_data['format'];
            //file_put_contents( "/tmp/wordpress.log", __METHOD__ . print_r( $url, true ) . PHP_EOL, FILE_APPEND );

            //todo get thu
            if ($rokka_data) {
                $image = array();
                $image[] = $url;
                $image[] = $imageSizes[0];
                $image[] = $imageSizes[1];

            } else {
                return $image;
            }
        }


        return $image;
    }

    /**
     * @param $response
     */
    function rokka_prepare_image_for_js($response)
    {
        // rokka_create_stacks();
        //basename( $file );
        //$stacks = list_thumbnail_sizes();
        //file_put_contents( "/tmp/wordpress.log", 'rokka_prepare_image_for_js: ' . print_r( $response, true ) . PHP_EOL, FILE_APPEND );

        //file_put_contents("/tmp/wordpress.log", 'post' . print_r($response,true) . PHP_EOL, FILE_APPEND);
        /*
         *  [id] => 35
            [title] => murica
            [filename] => murica-7.jpg
            [url] => http://localhost:8080/wp-content/uploads/2016/10/murica-7.jpg
            [link] => http://localhost:8080/?attachment_id=35
            [alt] =>
            [author] => 1
            [description] =>
            [caption] =>
            [name] => murica
            [status] => inherit
            [uploadedTo] => 2
            [date] => 1476358285000
            [modified] => 1480956176000
            [menuOrder] => 0
            [mime] => image/jpeg
            [type] => image
            [subtype] => jpeg
            [icon] => http://localhost:8080/wp-includes/images/media/default.png
            [dateFormatted] => October 13, 2016
            [nonces] => Array
                (
                    [update] => 586a114079
                    [delete] => d401bbc0d2
                    [edit] => aef6586ae6
                )

            [editLink] => http://localhost:8080/wp-admin/post.php?post=35&action=edit
            [meta] =>
            [authorName] => admin
            [uploadedToLink] => http://localhost:8080/wp-admin/post.php?post=2&action=edit
            [uploadedToTitle] => Sample Page
            [filesizeInBytes] => 228576
            [filesizeHumanReadable] => 223 kB
            [sizes] => Array
                (
                    [thumbnail] => Array
                        (
                            [height] => 150
                            [width] => 150
                            [url] => http://localhost:8080/wp-content/uploads/2016/10/murica-7-150x150.jpg
                            [orientation] => landscape
                        )

                    [medium] => Array
                        (
                            [height] => 169
                            [width] => 300
                            [url] => http://localhost:8080/wp-content/uploads/2016/10/murica-7-300x169.jpg
                            [orientation] => landscape
                        )

                    [large] => Array
                        (
                            [height] => 371
                            [width] => 660
                            [url] => http://localhost:8080/wp-content/uploads/2016/10/murica-7-1024x576.jpg
                            [orientation] => landscape
                        )

                    [full] => Array
                        (
                            [url] => http://localhost:8080/wp-content/uploads/2016/10/murica-7.jpg
                            [height] => 576
                            [width] => 1024
                            [orientation] => landscape
                        )

                )

            [height] => 576
            [width] => 1024
            [orientation] => landscape
            [compat] => Array
                (
                    [item] =>
                    [meta] =>
                )

        )

         */
        if (!$attachment = get_post($attachment)) {
            return;
        }

        if ('attachment' != $attachment->post_type) {
            return;
        }

        $meta = wp_get_attachment_metadata($attachment->ID);
        if (false !== strpos($attachment->post_mime_type, '/')) {
            list($type, $subtype) = explode('/', $attachment->post_mime_type);
        } else {
            list($type, $subtype) = array($attachment->post_mime_type, '');
        }

        $attachment_url = wp_get_attachment_url($attachment->ID);

        $response = array(
            'id' => $attachment->ID,
            'title' => $attachment->post_title,
            'filename' => wp_basename(get_attached_file($attachment->ID)),
            'url' => $attachment_url,
            'link' => get_attachment_link($attachment->ID),
            'alt' => get_post_meta($attachment->ID, '_wp_attachment_image_alt', true),
            'author' => $attachment->post_author,
            'description' => $attachment->post_content,
            'caption' => $attachment->post_excerpt,
            'name' => $attachment->post_name,
            'status' => $attachment->post_status,
            'uploadedTo' => $attachment->post_parent,
            'date' => strtotime($attachment->post_date_gmt) * 1000,
            'modified' => strtotime($attachment->post_modified_gmt) * 1000,
            'menuOrder' => $attachment->menu_order,
            'mime' => $attachment->post_mime_type,
            'type' => $type,
            'subtype' => $subtype,
            'icon' => wp_mime_type_icon($attachment->ID),
            'dateFormatted' => mysql2date(__('F j, Y'), $attachment->post_date),
            'nonces' => array(
                'update' => false,
                'delete' => false,
                'edit' => false
            ),
            'editLink' => false,
            'meta' => false,
        );

        $author = new WP_User($attachment->post_author);
        $response['authorName'] = $author->display_name;

        if ($attachment->post_parent) {
            $post_parent = get_post($attachment->post_parent);
        } else {
            $post_parent = false;
        }

        if ($post_parent) {
            $parent_type = get_post_type_object($post_parent->post_type);
            if ($parent_type && $parent_type->show_ui && current_user_can('edit_post', $attachment->post_parent)) {
                $response['uploadedToLink'] = get_edit_post_link($attachment->post_parent, 'raw');
            }
            $response['uploadedToTitle'] = $post_parent->post_title ? $post_parent->post_title : __('(no title)');
        }

        $attached_file = get_attached_file($attachment->ID);

        if (isset($meta['filesize'])) {
            $bytes = $meta['filesize'];
        } elseif (file_exists($attached_file)) {
            $bytes = filesize($attached_file);
        } else {
            $bytes = '';
        }

        if ($bytes) {
            $response['filesizeInBytes'] = $bytes;
            $response['filesizeHumanReadable'] = size_format($bytes);
        }

        if (current_user_can('edit_post', $attachment->ID)) {
            $response['nonces']['update'] = wp_create_nonce('update-post_' . $attachment->ID);
            $response['nonces']['edit'] = wp_create_nonce('image_editor-' . $attachment->ID);
            $response['editLink'] = get_edit_post_link($attachment->ID, 'raw');
        }

        if (current_user_can('delete_post', $attachment->ID)) {
            $response['nonces']['delete'] = wp_create_nonce('delete-post_' . $attachment->ID);
        }

        if ($meta && 'image' === $type) {
            $sizes = array();

            /** This filter is documented in wp-admin/includes/media.php */
            $possible_sizes = apply_filters('image_size_names_choose', array(
                'thumbnail' => __('Thumbnail'),
                'medium' => __('Medium'),
                'large' => __('Large'),
                'full' => __('Full Size'),
            ));
            unset($possible_sizes['full']);

            // Loop through all potential sizes that may be chosen. Try to do this with some efficiency.
            // First: run the image_downsize filter. If it returns something, we can use its data.
            // If the filter does not return something, then image_downsize() is just an expensive
            // way to check the image metadata, which we do second.
            foreach ($possible_sizes as $size => $label) {

                /** This filter is documented in wp-includes/media.php */
                if ($downsize = apply_filters('image_downsize', false, $attachment->ID, $size)) {
                    if (!$downsize[3]) {
                        continue;
                    }
                    $sizes[$size] = array(
                        'height' => $downsize[2],
                        'width' => $downsize[1],
                        'url' => $downsize[0],
                        'orientation' => $downsize[2] > $downsize[1] ? 'portrait' : 'landscape',
                    );
                } elseif (isset($meta['sizes'][$size])) {
                    if (!isset($base_url)) {
                        $base_url = str_replace(wp_basename($attachment_url), '', $attachment_url);
                    }

                    // Nothing from the filter, so consult image metadata if we have it.
                    $size_meta = $meta['sizes'][$size];

                    // We have the actual image size, but might need to further constrain it if content_width is narrower.
                    // Thumbnail, medium, and full sizes are also checked against the site's height/width options.
                    list($width, $height) = image_constrain_size_for_editor($size_meta['width'], $size_meta['height'], $size, 'edit');

                    $sizes[$size] = array(
                        'height' => $height,
                        'width' => $width,
                        'url' => $base_url . $size_meta['file'],
                        'orientation' => $height > $width ? 'portrait' : 'landscape',
                    );
                }
            }

            $sizes['full'] = array('url' => $attachment_url);

            if (isset($meta['height'], $meta['width'])) {
                $sizes['full']['height'] = $meta['height'];
                $sizes['full']['width'] = $meta['width'];
                $sizes['full']['orientation'] = $meta['height'] > $meta['width'] ? 'portrait' : 'landscape';
            }

            $response = array_merge($response, array('sizes' => $sizes), $sizes['full']);
        } elseif ($meta && 'video' === $type) {
            if (isset($meta['width'])) {
                $response['width'] = (int)$meta['width'];
            }
            if (isset($meta['height'])) {
                $response['height'] = (int)$meta['height'];
            }
        }

        if ($meta && ('audio' === $type || 'video' === $type)) {
            if (isset($meta['length_formatted'])) {
                $response['fileLength'] = $meta['length_formatted'];
            }

            $response['meta'] = array();
            foreach (wp_get_attachment_id3_keys($attachment, 'js') as $key => $label) {
                $response['meta'][$key] = false;

                if (!empty($meta[$key])) {
                    $response['meta'][$key] = $meta[$key];
                }
            }

            $id = get_post_thumbnail_id($attachment->ID);
            if (!empty($id)) {
                list($src, $width, $height) = wp_get_attachment_image_src($id, 'full');
                $response['image'] = compact('src', 'width', 'height');
                list($src, $width, $height) = wp_get_attachment_image_src($id, 'thumbnail');
                $response['thumb'] = compact('src', 'width', 'height');
            } else {
                $src = wp_mime_type_icon($attachment->ID);
                $width = 48;
                $height = 64;
                $response['image'] = compact('src', 'width', 'height');
                $response['thumb'] = compact('src', 'width', 'height');
            }
        }
    }


    /**
     * @param $post
     * @param $attachment
     * @return mixed
     */
    function rokka_attachment_fields_to_save($post, $attachment)
    {
        //file_put_contents( "/tmp/wordpress.log", 'rokka_attachment_fields_to_save' . PHP_EOL, FILE_APPEND );

        //file_put_contents( "/tmp/wordpress.log", 'post' . $post . PHP_EOL, FILE_APPEND );
        //file_put_contents( "/tmp/wordpress.log", 'post' . $attachment . PHP_EOL, FILE_APPEND );

        /// die(dump($id));
        return $post;
    }


    /**
     *
     * @return array
     */
    function rokka_create_stacks()
    {
        // require_once wp-includes/media.php
        $sizes = $this->list_thumbnail_sizes();
        //$client = \Rokka\Client\Factory::getImageClient('liip-development', 'uE4k49yVZsJQtcKxq8ABSLnmcz3Ny6Hs', 'S3OKwwPJwkqONwPdnadi1wmyior0siKs');
        $client = $this->rokka_get_client();

        $stacks = $client->listStacks();

        if (!empty($sizes)) {
            foreach ($sizes as $name => $size) {
                $continue = true;

                foreach ($stacks->getStacks() as $stack) {

                    if ($stack->name == $name) {
                        $continue = false;
                        continue;
                    }
                }
                if ($continue && $size[0] > 0) {
                    $resize = new \Rokka\Client\Core\StackOperation('resize', [
                        'width' => $size[0],
                        //'height' => $size[1]
                        'height' => 10000,
                        //aspect ratio will be kept
                        'mode' => 'box'
                    ]);

                    $return = $client->createStack($name, [$resize]);
                }
            }
        }

        return $sizes;
    }


    /**
     * @param $fileArray
     * @return mixed
     */
    function rokka_wp_handle_upload($fileArray)
    {


        $defaultNoopStackName = 'rokka_source'; //todo put to config if nescessary

        //die(var_dump($fileArray));

        /* fileArray structure
        array (size=3)
          'file' => string '/Users/philou/Documents/development/woocommerce-demo/wp/wp-content/uploads/2016/12/gehirn-4.png' (length=95)
          'url' => string 'http://localhost:8080/wp-content/uploads/2016/12/gehirn-4.png' (length=61)
          'type' => string 'image/png' (length=9)
        */

        //file_put_contents( "/tmp/wordpress.log", 'rokka_wp_handle_upload' . PHP_EOL, FILE_APPEND );


        //todo update meta files using the function below from wp-includes/post.php not sure how to include though
        //  update_post_meta( $post_id, $meta_key, $meta_value, $prev_value = '' );


        //wp_die($client);
        //$resize = new \Rokka\Client\Core\StackOperation('resize', ['width' => 200, 'height' => 200]);

        //$stackOperationCollection = new \Rokka\Client\Core\StackOperationCollection([$resize]);

        //$stack = $client->createStack('thumbnail', $stackOperationCollection);

        //var_dump($stack);
        //file_put_contents("/tmp/wordpress.log", 'array' . var_dump($fileArray) . PHP_EOL, FILE_APPEND);

        /// die(dump($id));

        /* //todo this stack stuff needs to happen somewhere else
        $resize = new \Rokka\Client\Core\OperationCollection();

        //$stackOperationCollection = new \Rokka\Client\Core\StackOperationCollection([$resize]);
        $stackOperationCollection = new \Rokka\Client\Core\StackOperation();

        $stack = $client->createStack('mystack', $stackOperationCollection);

        var_dump($stack);

        file_put_contents("/tmp/wordpress.log", print_r($fileArray,null) . PHP_EOL, FILE_APPEND);
        */

        return $fileArray;
    }
}