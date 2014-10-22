<?php

/*
 * The MIT License
 *
 * Copyright 2014 will.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Bread\Structures;

/**
 * Description of BreadNotification
 *
 * @author will
 */

//<li style="margin: 15px; color: black;" class="">
//  <div id="notify" class="dropdown open">
//    <a data-toggle="dropdown">Notifications<span class="badge">2</span></a>
//    <ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
//    <li role="presentation">
//      <div class="media">
//        <div class="media-body">
//  <h4 class="media-heading">Title</h4>
//  Details
//</div>
//<hr>
//<div class="media-body">
//  <h4 class="media-heading">Title</h4>
//  Details
//</div>
//      </div>
//    </li>
//  </ul>
//</div>
//</li>
class BreadNotification {
    //put your code here
    /**
     * @var string Title of the information.
     */
    public $title;
    /**
     * @var string Detailed information about the notification.
     */
    public $description;
    /**
     * @var array Only send users with these rights.
     */
    public $rights = [];
    /**
     * How long should the notification be on the stack for in seconds? Defaults to 6 hours.
     * @var integer 
     */
    public $duration = 21600; //6 hours
    /**
     * Time when the notification was sent.
     * @var integer
     */
    public $timesent = 0;
    
}
