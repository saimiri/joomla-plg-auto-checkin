<?php

/**
 * Description of regex
 *
 * Copyright 2017 Juha Auvinen.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @copyright   Copyright (c) 2017 Juha Auvinen (http://www.github.com/saimiri)
 * @author      Juha Auvinen <juha@saimiri.fi>
 * @license     http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link        http://www.github.com/saimiri
 * @since       File available since Release 1.0.0
 */
class JFormRuleRegex extends JFormRule
{
	public function test( SimpleXMLElement $element, $value, $group = null, JRegistry $input = null, JForm $form = null )
	{
		$this->regex = (string)$element['regex'];
		return parent::test( $element, $value, $group, $input, $form );
	}
}