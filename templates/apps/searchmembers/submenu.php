<?php
/*

Copyright (c) 2007 BeVolunteer

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

$words = new MOD_words();
?>
      <div id="middle_nav" class="clearfix">
        <div id="nav_sub">
          <ul> 
            <li id="sub1" class="active">
			<a style="cursor:pointer;" onClick="$('FindPeopleFilter').toggle(); $('sub1').addClassName('active'); $('sub1').siblings().each(Element.removeClassName('active');">
			<span><?php echo $words->get('Simple Search'); ?>Simple search</span></a></li>
            <li id="sub2"><a style="cursor:pointer;" onClick="$('sub2').siblings().each(Element.removeClassName('active');"><span>Add Filters</span></a></li>
            <li id="sub3"><a style="cursor:pointer;" onClick="$('FindPeopleFilter').toggle(); $('sub3').addClassName('active'); $('sub3').siblings().each(Element.removeClassName('active');"><span>Results Order</span></a></li>

            <li id="sub4"><a style="cursor:pointer;" onClick="$('FindPeopleFilter').toggle(); $('sub4').addClassName('active'); $('sub4').siblings().each(Element.removeClassName('active');"><span>whatever</span></a></li>
          </ul>
        </div>
      </div>

