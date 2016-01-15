<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\Event\Listener;

use BackBee\Event\Event;

/**
 * Layout Selector Listener
 *
 * @author Bogdan Oanes <bogdan.oanes@lp-digital.fr>
 */
class LayoutSelectorListener extends Event
{
    private static $application;

    public static function onPostCall(Event $event)
    {
        self::$application = $event->getDispatcher()->getApplication();

        # Get YML configuration for default layout
        $config = self::$application->getConfig()->getSection('default_layout');

        # Check if default layout is defined
        if (!$config['default_layout']) {
            return;
        }

        $response = $event->getResponse();
        $layoutObjs = $response->getContent() ? json_decode($response->getContent()) : array(); 

        # Reorder layouts. First layout will be the one defined in the YML
        $defaultLayout = $otherLayouts = [];
        foreach ($layoutObjs as $layout) {
            if ($layout->uid == $config['default_layout']) {
                $defaultLayout[] = $layout;
            } else {
                $otherLayouts[] = $layout;
            }
        }

        # Set new content
        $response->setContent(
            json_encode(
                array_merge(
                    $defaultLayout, 
                    $otherLayouts 
                )
            )
        );
    }
}