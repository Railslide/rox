<?php
/*
Copyright (c) 2007-2009 BeVolunteer

This file is part of BW Rox.

BW Rox is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

BW Rox is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, see <http://www.gnu.org/licenses/> or
write to the Free Software Foundation, Inc., 59 Temple Place - Suite 330,
Boston, MA  02111-1307, USA.
*/

    /**
     * @author Fake51
     */

    /**
     * base page for all blog pages
     *
     * @package Apps
     * @subpackage Blog
     */

class TripsBasePage extends PageWithActiveSkin
{
    protected $current = false;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * creates a pager and inits it with some params
     *
     * @param int $elements
     * @param int $page
     * @access public
     */
    public function initPager($url, $elements, $page = 1, $items_per_page = TripsController::TRIPS_PER_PAGE)
    {
        $params = new StdClass;
        $params->strategy = new HalfPagePager('right');
        $params->items = $elements;
        $params->items_per_page = $items_per_page;
        $params->page_url = 'trips/' . $url;
        $params->page_url_marker = 'page';
        $params->page_method = 'url';
        $params->page = $page;
        $this->pager = new PagerWidget($params);
    }

    protected function getSubmenuItems()
    {
        $items = array();
        $layoutkit = $this->layoutkit;
        $words = $layoutkit->getWords();
        $items[] = array('mytrips', 'trips/mytrips', $words->getSilent('TripsMyTrips'));
        $items[] = array('upcomingtrips', 'trips/upcoming', $words->getSilent('TripsUpcoming'));
        $items[] = array('pasttrips', 'trips/past', $words->getSilent('TripsPastTrips'));
        $geo = new Geo($this->member->IdCity);
        $items[] = array('tripsnearme', 'trips/nearme', $words->getSilent('TripsTripsNear', $geo->name));
        if ($this->update) {
            $items[] = array('edittrips', 'trips/' . $this->trip->id . '/edit', $words->getSilent('TripsEdit'));
        } else {
            $items[] = array('createtrips', 'trips/create', $words->getSilent('TripsCreate'));
        }
        if ($this->delete) {
            $items[] = array('deltrips', 'trips/' . $this->trip->id . '/delete', $words->getSilent('TripsDelete'));
        }
        if ($this->show) {
            $items[] = array('showtrip', 'trips/show/' . $this->trip->id, $words->getSilent('TripsShow'));
        }
        return $items;
    }

    protected function getPageTitle() {
        $words = $this->getWords();
        return $words->getBuffered('Trips') . ' - BeWelcome';
    }

    protected function teaserContent()
    {
        $layoutkit = $this->layoutkit;
        $formkit = $layoutkit->formkit;
        $callbackTags = $formkit->setPostCallback('TripsController', 'searchTripsCallback');
        $words = $layoutkit->getWords();

        require(SCRIPT_BASE . 'build/trips/templates/teaser.php');
    }

    protected function getColumnNames()
    {
        // we don't need the other columns
        return array('col3');
    }

//    protected function leftSideBar()
//    {
//        if (!$this->member)
//        {
//            return false;
//        }
//        require SCRIPT_BASE . 'build/trip/templates/userbar.php';
//    }
//
//    protected function teaserContent()
//    {
//        if (!$this->member)
//        {
//            require SCRIPT_BASE . "build/trip/templates/teaser_public.php";
//        }
//        else
//        {
//            $userHandle = $this->member->Username;
//            require SCRIPT_BASE . "build/trip/templates/teaser.php";
//        }
//    }

    protected function getStylesheets()
    {
        $stylesheets = parent::getStylesheets();
        $stylesheets[] = 'script/leaflet/0.7.3/leaflet.css?1';
        $stylesheets[] = 'script/leaflet/plugins/Leaflet.markercluster/0.4.0/MarkerCluster.Default.css';
        $stylesheets[] = 'script/leaflet/plugins/Leaflet.markercluster/0.4.0/MarkerCluster.css';
        return $stylesheets;
    }

    public function getLateLoadScriptfiles() {
        $scriptFiles = parent::getLateLoadScriptfiles();
        $scriptFiles[] = 'jquery-ui-1.11.2/jquery-ui.min.js';
        $scriptFiles[] = 'leaflet/0.7.3/leaflet.js';
        $scriptFiles[] = 'leaflet/plugins/shramov-leaflet-plugins/1.1.0/layer/tile/Google.js';
        $scriptFiles[] = 'map/leaflet/LeafletFlagIcon.js?1';
        $scriptFiles[] = 'map/initMap.js';
        return $scriptFiles;
    }
}
