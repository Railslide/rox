<?php

/*

Copyright (c) 2007 BeVolunteer

This file is part of BW Rox.

BW Rox is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

Foobar is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, see <http://www.gnu.org/licenses/> or 
write to the Free Software Foundation, Inc., 59 Temple Place - Suite 330, 
Boston, MA  02111-1307, USA.

*/


require_once ("menus.php");

function DisplayError($ErrorMessage = "No Error Message") {
	global $title, $errcode;
	$title = ww('ErrorPage');

	require_once "header.php";

	Menu1("error.php", ww('MainPage')); // Displays the top menu
	Menu2($_SERVER["PHP_SELF"]); // Display the second menu
	DisplayHeaderShortUserContent($errcode); // Display the heade

	echo "        <div class=\"info\">";
	echo "<p>", $ErrorMessage, "</p>";
	echo "        </div>";

	require_once "footer.php";
	exit (0); // To be sure that member doesn't go further after an error
}
?>