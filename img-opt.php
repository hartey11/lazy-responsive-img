<?php

function meaningfulAltText($filename)
{
    //If first character is a number
    if (is_numeric(substr($filename, 0, 1))) {
        global $post;
        // return post title with number appended to the end.
        return $post->post_title . ' - ' . $filename;
    } else {
        //return filename with white spaces and first letter capitalised.
        return ucfirst(str_replace(["-", "_"], " ", $filename));
    }
}

// Get image data, used for responsive images.
function get_img_data($image_id)
{
    if (!$image_id) {
        return null;
    }

    // Get image URL
    $image['src'] = wp_get_attachment_image_src($image_id, 'full');

    // Make image URL relative path for the images - Makes it easier when using localhost for dev!
    $image['src'] = wp_make_link_relative($image['src'][0]);

    // Get the file extension of the image
    $image['extension'] = pathinfo($image['src'], PATHINFO_EXTENSION) . '/';

    // Get image details
    $image['details'] = wp_get_attachment_metadata($image_id);

    $filename = pathinfo($image['src'], PATHINFO_FILENAME);

    if ($image['extension'] === 'svg/') {
        // Get absolute server path to extract the svg.
        $image['path'] = get_attached_file($image_id);
        $image['svg'] = file_get_contents($image['path']);
        $xml = new \SimpleXMLElement($image['svg']);

        //Get width and height to calculate the padding-top of the container.
        if ($xml->attributes()['viewBox']) {
            $viewbox = explode(' ',
                $xml->attributes()['viewBox']);
            $details = [
                //+0 hack to cast as either int or float instead of a string.
                //https://stackoverflow.com/questions/16606364/cast-string-to-either-int-or-float
                'height' => $viewbox[3] + 0,
                'width' => $viewbox[2] + 0,
            ];
            $image['details'] = $details;
        }
    } else {
        $image['alt'] = get_post_meta($image_id, '_wp_attachment_image_alt', true);
        // Get relative directory path for media query sizes
        $image['path'] = pathinfo($image['src'], PATHINFO_DIRNAME) . '/';

        if (empty($image['alt']) || $image['alt'] === $filename) {
            $image['alt'] = meaningfulAltText($filename);
        }
    }

    // Add post details for img alt and title attributes
    $image['post'] = get_post($image_id);

    if (empty($image['post']->post_title) || $image['post']->post_title === $filename) {
        $image['post']->post_title = meaningfulAltText($filename);
    }

    return $image;
}

function set_img_src($image)
{
    if ($image['details']['sizes']) {

        foreach ($image['details']['sizes'] as $size => $attributes) {
            if ($size === 'thumbnail') {
                continue;
            }
            $src['sizes'][$size]['file'] = $image['path'] . $attributes['file'];
            $src['sizes'][$size]['height'] = $attributes['height'];
            $src['sizes'][$size]['width'] = $attributes['width'];
        }
        $src['sizes']['full']['file'] = $image['src'];
        $src['sizes']['full']['height'] = $image['details']['height'];
        $src['sizes']['full']['width'] = $image['details']['width'];

        // Sort image sizes largest first.
        uasort($src['sizes'], function ($a, $b) {
            return $b['width'] <=> $a['width'];
        });

    } else {
        $src = $image['src'];
    }

    return htmlspecialchars(json_encode($src), ENT_QUOTES, 'UTF-8');
}

//Responsive Images using the picture element or embedding SVG within a span
function optimised_image($image_id = null, $aspect_ratio = true, $classes = null, $id = null)
{

    if (!$image_id) {
        return null;
    }

    $image = get_img_data($image_id);

    $img = '<div';
    if ($id && empty($image['post']->post_excerpt)) {
        $img .= ' id="' . $id . '"';
    }
    $img .= ' class="img-opt';
    if ($classes && empty($image['post']->post_excerpt)) {
        $img .= ' ' . $classes;
    }
    $img .= '"><div';
    if ($aspect_ratio) {
        if (is_string($aspect_ratio)) {
            $padding = $aspect_ratio;
        } else {
            $padding = ($image['details']['height'] / $image['details']['width'] * 100) . '%';
        }
        $img .= ' style="padding-top: ' . $padding . '"';
    }
    $img .= '>';

    if ($image['extension'] === 'svg/') {
        $img .= $image['svg'];
    } else {
        $src = set_img_src($image);
        $img .= '<img src=""';
        $img .= ' data-src="' . $src . '"';
        if ($image['alt']) {
            $img .= ' alt="' . $image['alt'] . '"';
        }
        if ($image['post']->post_title) {
            $img .= ' title="' . $image['post']->post_title . '"';
        }
        $img .= '>';
    }

    $img .= '</div></div>';

//    TODO: see if this is needed at all or done outside of the function, might need a parameter to control this.
//    if ($image['post']->post_excerpt) {
//        $figure = '<figure';
//        if ($id) {
//            $figure .= ' id="' . $id . '"';
//        }
//        if ($classes) {
//            $figure .= ' id="' . $classes . '"';
//        }
//        $figure .= '>' . $img . '<figcaption>' . $image['post']->post_excerpt
//            . '</figcaption></figure>';
//        return $figure;
//    } else {
//        return $img;
//    }

    return $img;
}
