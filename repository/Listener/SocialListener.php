<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee Standard Edition.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee Standard Edition is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee Standard Edition. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Event\Listener;

use BackBee\ClassContent\Social\Twitter;
use BackBee\Renderer\Event\RendererEvent;

/**
 * Social network Listener
 *
 * @author f.kroockmann <florian.kroockmann@lp-digital.fr>
 * @author Mickaël Andrieu <mickael.andrieu@lp-digital.fr>
 */
class SocialListener
{
    const WIDGET_API_URL = 'https://twitter.com/settings/widgets/';

    private static $application;

    public static function onRenderFacebook(RendererEvent $event)
    {
        $renderer = $event->getRenderer();
        self::$application = $event->getApplication();

        $config = self::getSocialConfig('facebook');

        $content = $event->getTarget();

        $link = $content->getParamValue('link');
        if (empty($link)) {
            if (null !== $config && isset($config['link'])) {
                $link = $config['link'];
            }
        }

        $showPost = $content->getParamValue('show_post');
        $hideCover = $content->getParamValue('hide_cover');

        $renderer->assign('link', $link)
                 ->assign('show_post', in_array('show_post', $showPost))
                 ->assign('hide_cover', in_array('hide_cover', $hideCover))
                 ->assign('height', $content->getParamValue('height'));
    }

    public static function onRenderTwitter(RendererEvent $event)
    {
        $renderer = $event->getEventArgs();
        self::$application = $event->getDispatcher()->getApplication();

        $config = self::getSocialConfig('twitter');

        $content = $renderer->getObject();

        $widgetId = $content->getParamValue('widget_id');

        if (empty($widgetId) || !self::checkTwitterId($widgetId)) {
            if (null !== $config && isset($config['widget_id'])) {
                $widgetId = $config['widget_id'];
            }
        }

        $renderer->assign('widget_id', $widgetId);
    }

    private static function getSocialConfig($key)
    {
        $socialsNetworks = self::$application->getConfig()->getSection('social_network');
        $config = null;

        if ($socialsNetworks !== null && isset($socialsNetworks[$key])) {
            $config = $socialsNetworks[$key];
        }

        return $config;
    }

    private static function checkTwitterId($widgetId)
    {
        $resourceUrl = self::WIDGET_API_URL . $widgetId;
        $resourceExists = false;

        $ch = curl_init($resourceUrl);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if($statusCode == '200'){
            $resourceExists = true;
        }

        return $resourceExists;
    }
}
