<?php
/*
 * Copyright 2014 NxProGA\Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

namespace NxProGA\Google\Service\Analytics\Resource;

/**
 * The "management" collection of methods.
 * Typical usage is:
 *  <code>
 *   $analyticsService = new NxProGA\Google\Service\Analytics(...);
 *   $management = $analyticsService->management;
 *  </code>
 */
class Management extends \NxProGA\Google\Service\Resource
{
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Management::class, 'Google_Service_Analytics_Resource_Management');