<?php

/*
 * The MIT License
 *
 * Copyright 2014 Half-Shot.
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

namespace Bread\Settings;

/**
 * Description of SettingsInterface
 *
 * @author will
 */
interface SettingsInterface {
    /**
     * Check to see if the requested File exists.
     * @param \Bread\Settings\SettingsFile $File
     */
    function SettingExists(SettingsFile $File);
    /**
     * Create and save an empty file from template.
     * @param \Bread\Settings\SettingsFile $File
     * @param stdClass $Template
     */
    function CreateSetting(SettingsFile $File,$Template);
    /**
     * Retrieve a stdobject from a source.
     * @param \Bread\Settings\SettingsFile $File
     */
    function RetriveSettings(SettingsFile $File);
    /**
     * Save a setting back to it's source.
     * @param \Bread\Settings\SettingsFile $File
     * @param bool $ShouldThrow Should you be worried about a failed save.
     */
    function SaveSetting(SettingsFile $File,$ShouldThrow = true);
    /**
     * Delete a setting's source.
     * @param \Bread\Settings\SettingsFile $File
     */
    function DeleteSetting(SettingsFile $File);
}
