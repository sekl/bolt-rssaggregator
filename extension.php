<?php
// RSS Aggregator Extension for Bolt, by Sebastian Klier

namespace RSSAggregator;

class Extension extends \Bolt\BaseExtension
{

    /**
     * Info block for RSS Aggregator Extension.
     */
    function info()
    {

        $data = array(
            'name' => "RSS Aggregator",
            'description' => "Shows feed items of external RSS feeds anywhere on your site.",
            'keywords' => "bolt, rss, feed, aggregator",
            'author' => "Sebastian Klier",
            'link' => "http://github.com/sekl/bolt-rssaggregator",
            'version' => "0.1",
            'required_bolt_version' => "1.0.2",
            'highest_bolt_version' => "1.1.4",
            'type' => "General",
            'first_releasedate' => "2013-08-29",
            'latest_releasedate' => "2013-08-29",
            'dependencies' => "",
            'priority' => 10
        );

        return $data;

    }

    /**
     * Initialize RSS Aggregator. Called during bootstrap phase.
     */
    function init()
    {

        // If yourextension has a 'config.yml', it is automatically loaded.
        // $foo = $this->config['bar'];

        // Add CSS file
        $this->addCSS("assets/rssaggregator.css");

        // Initialize the Twig function
        $this->addTwigFunction('rss_aggregator', 'twigRss_aggregator');

    }

    /**
     * Twig function {{ rss_aggregator() }} in RSS Aggregator extension.
     */
    function twigRss_aggregator($url = false, $options = array())
    {

        if(!$url) {
            return new \Twig_Markup('External feed could not be loaded! No URL specified.', 'UTF-8'); 
        }

        // Handle options parameter
        $defaultLimit = 5;
        $defaultShowDesc = false;
        $defaultShowDate = false;
        $defaultDescCutoff = 100;

        if(!array_key_exists('limit', $options)) {
            $options['limit'] = $defaultLimit;
        }
        if(!array_key_exists('showDesc', $options)) {
            $options['showDesc'] = $defaultShowDesc;
        }
        if(!array_key_exists('showDate', $options)) {
            $options['showDate'] = $defaultShowDate;
        }
        if(!array_key_exists('descCutoff', $options)) {
            $options['descCutoff'] = $defaultDescCutoff;
        }

        // Make sure we are sending a user agent header with the request
        $streamOpts = array(
            'http' => array(
                'user_agent' => 'libxml',
            )
        );

        libxml_set_streams_context(stream_context_create($streamOpts));

        $doc = new \DOMDocument();

        // Load feed and suppress errors to avoid a failing external URL taking down our whole site
        if (!@$doc->load($url)) {
            return new \Twig_Markup('External feed could not be loaded!', 'UTF-8');
        }

        // Parse document
        $feed = array();

        foreach($doc->getElementsByTagName('item') as $node) {
            $item = array(
                'title' => $node->getElementsByTagName('title')->item(0)->nodeValue,
                'desc' => $node->getElementsByTagName('description')->item(0)->nodeValue,
                'link' => $node->getElementsByTagName('link')->item(0)->nodeValue,
                'date' => $node->getElementsByTagName('pubDate')->item(0)->nodeValue,
            );
            array_push($feed, $item);
        }

        $items = array();

        for($i = 0; $i < $options['limit']; $i++) {
                $title = htmlentities(strip_tags($feed[$i]['title']), ENT_QUOTES, "UTF-8");
                $link = htmlentities(strip_tags($feed[$i]['link']), ENT_QUOTES, "UTF-8");
                $desc = htmlentities(strip_tags($feed[$i]['desc']), ENT_QUOTES, "UTF-8");
                $desc = substr($desc, 0, strpos($desc, ' ', $options['descCutoff']));
                $desc = str_replace('&amp;nbsp;', '', $desc);
                $desc .= '...';
                $date = date('l F d, Y', strtotime($feed[$i]['date']));
                array_push($items, array(
                    'title' => $title,
                    'link'  => $link,
                    'desc'  => $desc,
                    'date'  => $date,
                ));
        }

        $html = '<div class="rss-aggregator"><ul>';

        foreach ($items as $item) {
            $html .= '<li>';
            $html .= '<a href="' . $item['link'] . '" class="rss-aggregator-title" rel="nofollow">' . $item['title'] . '</a><br />';
            if ($options['showDesc']) {
                $html .= '<span class="rss-aggregator-desc">' . $item['desc'] . '</span>';
            }
            if ($options['showDate']) {
                $html .= '<span class="rss-aggregator-date">' . $item['date'] . '</span>';
            }
            $html .= '</li>';
        }

        $html .= '</ul></div>';

        return new \Twig_Markup($html, 'UTF-8');
    }
}