<?php
	class bbcodeParser
	{
		private $errors = Array();
		private $parseText;
		private $resultText = null;
		private $lineNumber = 1;

		public function __construct($parseText)
		{
			$this -> parseText = $parseText;
		}

		public function getParsed()
		{
			if($this -> resultText === null)
			{
				$this -> resultText = $this -> parseTag($this -> parseText, 0, "root");
				$this -> yieldErrors();
			}
			
			return $this -> resultText;
		}

		private function addError($errorText)
		{
			$lineNumber = $this -> lineNumber;

			foreach($this -> errors as $key => $error)
			{
				// Check if this error is redundant, if so append to an existing error message to reduce spammage
				if($error['errorText'] == $errorText)
				{
					// Append to this error entry
					$this -> errors[$key]['errorCount']++;

					foreach($this -> errors[$key]['lineNumbers'] as $number)
					{
						// Check if this line number has already be mentioned, again to reduce spammage
						if($number == $lineNumber)
							return;
					}

					array_push($this -> errors[$key]['lineNumbers'], $lineNumber);
					return;
				}
			}

			$newError = Array('errorText' => $errorText, 'errorCount' => 1, 'lineNumbers' => Array($lineNumber));
			array_push($this -> errors, $newError);
		}

		private function yieldErrors()
		{
			foreach($this -> errors as $error)
			{
				if($error['errorCount'] > 1)
					$errorCount = "(" . $error['errorCount'] . ")";
				else
					$errorCount = "";

				if(count($error['lineNumbers']) > 1)
				{
					$lineNumbers = "On lines";

					foreach($error['lineNumbers'] as $key => $lineNumber)
						$lineNumbers = $lineNumbers . ($key == 0 ? " " : ", ") . $lineNumber;
				}
				else
					$lineNumbers = "On line " . $error['lineNumbers'][0];

				warn("BBCode Parser: {$error['errorText']} $errorCount $lineNumbers.");
			}
		}

		// Convert bbcode formatted text into html
		// Taking the java-ish approach to this
		// Not bothering with regex because it's basically unreadable that way

		private function parseTag($tagText, $level, $parentTag)
		{
			if(!isSet($level))
				$level = 0;

			if($level > 25)
			{
				throw new Exception("Reached max nested tag parsing level.");
				return false;
			}

			$tagText = trim(html_entity_decode($tagText));
			$cursor = -1;
			$length = strlen($tagText);
			$char = "";
			$searchCursor = 0;
			$searchChar = "";

			$newText = "";
			$stringBuffer = "";

			while($cursor <= $length)
			{
				$stringBuffer = "";
				while($cursor <= $length)
				{
					$cursor++;
					$stringBuffer = $stringBuffer . $char;
					$char = charAt($tagText, $cursor);

					if($char == "\n" || $char == "\r" || $char == "[")
						break;
				}

				$newText = $newText . htmlentities($stringBuffer, ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8");
				

				if($char == "\n")
				{
					$this -> lineNumber++;

					if($parentTag != "pre" && $parentTag != "code" && $parentTag != "table" && $parentTag != "tr")
						$newText = $newText . "<br />";
					continue;
				}
				else if($char == "\r")
				{
					$char = '';
					continue;
				}
				else if($char == "[") // Found the beginning of a tag?
				{
					// Try to find the end of the tag, if any
					$searchCursor = $cursor + 1;
					$found = false;

					while($searchCursor <= $length)
					{
						$searchChar = charAt($tagText, $searchCursor);

						if($searchChar == "[")
						{
							break;
						}
						else if($searchChar == "]") // Probably the end of the tag.
						{
							$found = true;
							break;
						}

						$searchCursor++;
					}

					if(!$found)
					{
						continue;
					}

					$startTag = subStr($tagText, $cursor + 1, $searchCursor - $cursor - 1);
					$startTagEndPos = $searchCursor;

					$startTagSearch = 0;
					$startTagLength = strlen($startTag);

					$tagName = "";
					$found = false;

					while($startTagSearch <= $startTagLength)
					{
						$searchChar = charAt($startTag, $startTagSearch);

						if(($searchChar == " " && strlen($tagName) < 1) || $searchChar == "[" || $searchChar == "]")
						{
							// Ignore.
						}
						else if($searchChar == "\r" || $searchChar == "\n" || $searchChar == "/")
						{
							// Character invalidates this tag.
							$found = true;
							break;
						}
						else if($searchChar == "=" || $searchChar == " ")
						{
							// Reached the end of the tag name.
							break;
						}
						else
						{
							$tagName = $tagName . $searchChar;	
						}
						

						$startTagSearch++;
					}

					if($found) // Found invalid character that invalidates the tag.
					{
						continue;
					}

					$tagName = strtolower($tagName);
					if(strlen($tagName) < 1) // No tag name.
					{
						continue;
					}

					
					$tagType = $this -> tagType($tagName, $parentTag);

					if($tagType === false)
					{
						$this -> addError("Found a [$tagName] tag, but it's not a recognized tag name.");
						continue;
					}
					else if($tagType === true)
					{
						$this -> addError("Found a [$tagName] tag, but it can't be used in that context.");
						continue;
					}

					$tagArgument = "";

					while($startTagSearch <= $startTagLength)
					{
						$searchChar = $searchChar = charAt($startTag, $startTagSearch);

						if(($searchChar == "=" && $startTagSearch < strlen($tagName) + 5) || $searchChar == "]")
						{
							// Ignore.
						}
						else
						{
							$tagArgument = $tagArgument . $searchChar;
						}

						$startTagSearch++;
					}

					$tagArgument = htmlentities($tagArgument);

					if($tagType > -1)
					{
						// We have to find the end tag in order to finish validating this tag.

						$searchCursor = $startTagEndPos + 1;
						$found = false;
						$nesting = 0;

						while($searchCursor <= $length)
						{
							$searchChar = charAt($tagText, $searchCursor);
							$tagNameLength = strlen($tagName);
							if($searchChar == "[") // Beginning of the end tag?
							{
								$test1 = charAt($tagText, $searchCursor + 1);
								if($test1 == "/")
								{
									// Ending tag
									$test2 = substr($tagText, $searchCursor + 2,  $tagNameLength + 1);
									if($test2 == ($tagName . "]"))
									{
										// End tag!!!!
										if($nesting > 0)
										{
											$nesting--;
										}
										else
										{
											$found = true;
											$endTagPos = $searchCursor;
											$endTagEndPos = $endTagPos + $tagNameLength + 1;
											$endTag = subStr($tagText, $endTagPos, $tagNameLength + 3);
											break;
										}
									}
								}
								else if(substr($tagText, $searchCursor + 1, $tagNameLength) == $tagName)
								{
									$close = strpos($tagText, "]", $searchCursor + $tagNameLength + 1);

									if($close === false)
										continue;

									$nesting++;
									$searchCursor = $searchCursor + $tagNameLength + 1;
								}
							}

							$searchCursor++;
						}

						if(!$found) // Didn't find a valid end tag.
						{
							$this -> addError("Found an open [$tagName] tag, but didn't find a matching close tag.");
							continue;
						}

						if($tagType == 1)
						{
							$processed = $this -> tagStartHTML($tagName, $tagArgument) . $this -> parseTag(substr($tagText, $startTagEndPos + 1, $endTagPos - $startTagEndPos - 1), $level + 1, $tagName) . $this -> tagEndHTML($tagName);

							$newText = $newText . $processed;
							$cursor = $endTagEndPos + 1;
						}
						else if($tagType == 2)
						{
							$processed = subStr($tagText, $startTagEndPos + 1, $endTagPos - $startTagEndPos - 1);
							$processed = htmlentities($processed, ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8");
							$newText = $newText . $processed;
							$cursor = $endTagEndPos + 1;
						}
						else // tagType = 0
						{
							$processed = $this -> tagStartHTML($tagName, subStr($tagText, $startTagEndPos + 1, $endTagPos - $startTagEndPos - 1));
							$newText = $newText . $processed;
							$cursor = $endTagEndPos + 1;
						}

						$char = '';
					}
					else
					{
						$newText = $newText . $this -> tagStartHTML($tagName, $tagArgument);
						$cursor = $startTagEndPos;
						$char = '';
					}
				}
			}
			return $newText;
		}


		// 2 = Escape tag (Text inside won't be parsed by bbcode)
		// 1 = Normal tag (contains text that will be further parsed e.g. [i]text[/i])
		// 0 = Text argument tag (The text contained in the tag is the argument for the tag e.g. [img]url[/img])
		// -1 = Self-closing tag (no end tag, creates a single element e.g. [hr])
		// Returns false if the tag doesn't exist.
		// Returns true if tag cannot be used in this context.
		private function tagType($tagName, $parentTag)
		{
			// Don't let tables get screwed up
			if($parentTag == "table" && $tagName != "tr")
				return true;
			else if($parentTag == "tr" && $tagName != "td")
				return true;

			switch($tagName)
			{
				case "i": // Italics
					return 1;

				case "u": //Underline
					return 1;

				case "b": //Bold
					return 1;

				case "s": //Strikethrough
					return 1;

				case "color":
					return 1;

				case "size":
					return 1;

				case "font":
					return 1;

				case "url":
					return 1;

				case "iurl":
					return 1;

				case "abbr":
					return 1;

				case "center":
					return 1;
					
				case "left":
					return 1;

				case "right":
					return 1;

				case "just":
					return 1;

				case "tt":
					return 1;

				case "pre":
					return 1;

				case "code":
					return 1;

				case "quote":
					return 1;

				case "table":
					return 1;

				case "tr":
					if($parentTag != "table")
					{
						return true;
					}
					return 1;

				case "td":
					if($parentTag != "tr")
					{
						return true;
					}
					return 1;
					



				case "img":
					return 0;

				case "image":
					return 0;

				case "audio":
					return 0;

				case "video":
					return 0;

				case "youtube":
					return 0;

				case "vimeo":
					return 0;



				case "hr":
					return -1;

				case "anchor":
					return -1;



				case "nobbc":
					return 2;

				case "noparse":
					return 2;

				default:
					return false;
			}
		}


		private function tagStartHTML($tagName, $argument)
		{
			$argument = html_entity_decode($argument);
			$argument = strip_tags($argument);
			$argument = str_replace(Array("<", ">", "{", "}", ";"), Array("%3C", "%3E", "%7B", "%7D", "%3B"), $argument);

			switch($tagName)
			{
				case "i":
					return '<i>';

				case "u":
					return '<span style="text-decoration: underline;">';

				case "b":
					return '<b>';

				case "s":
					return '<span style="text-decoration: line-through;">';

				case "color":
					return '<span style="color: ' . htmlentities($argument) . ';">';

				case "size":
					return '<span style="font-size: ' . htmlentities($argument) . ';">';

				case "font":
					return '<span style="font-family: \'' . htmlentities($argument) . '\';">';

				case "url":
					return '<a href="' . filter_url($argument) . '" target="_BLANK">';

				case "iurl":
					return '<a href="' . filter_url($argument) . '">';

				case "abbr":
					return '<span title="' . filter_uri($argument) . '">';

				case "center":
					return '<div style="display: inline-block; width: 100%; text-align: center; content-align: center;">';

				case "left":
					return '<div style="display: inline-block; width: 100%; text-align: left; content-align: left;">';

				case "right":
					return '<div style="display: inline-block; width: 100%; text-align: right; content-align: right;">';

				case "just":
					return '<div style="display: inline-block; width: 100%; text-align: justify;">';

				case "tt":
					return '<span style="font-family: monospace;">';

				case "pre":
					return '<pre>';

				case "code":
					return '<div class="blockquoteHead finetext">Code<blockquote class="codeTag">';

				case "quote":
					$author = (strlen($argument) > 0 ? 'Quote from: ' . htmlentities($argument) . '' : "");
					return '<div class="blockquoteHead finetext">' . $author . '<blockquote>';

				case "table":
					$border = "";
					if(trim($argument) == "border")
						$border = ' border="1"';

					return '<table class="bbcodeTable"' . $border . '>';

				case "tr":
					return '<tr class="bbcodeTable">';

				case "td":
					return '<td class="bbcodeTable">';



				case "img":
					return '<img class="postImage" src="' . filter_url(html_entity_decode($argument)) . '" loading="lazy">';

				case "image":
					return '<img class="postImage" src="' . filter_url(html_entity_decode($argument)) . '" loading="lazy">';

				case "audio":
					$url = filter_url(html_entity_decode($argument));
					return '<audio class="postMedia" preload="metadata" volume=0.3 controls><source src="' . $url . '" />Your browser does not support embedded audio content. <a href="' . $url . '" target="_BLANK">' . $url . '</a></audio>';

				case "video":
					$url = filter_url(html_entity_decode($argument));
					return '<video class="postMedia" preload="metadata" muted controls><source src="' . $url . '" />Your browser does not support embeded video content. <a href="' . $url . '" target="_BLANK">' . $url . '</a></video>';

				case "youtube":
					$videoUrl = parse_url($argument);
					$videoID = "";

					if(isSet($videoUrl['query']))
					{
						parse_str($videoUrl['query'], $videoQuery);

						if(isSet($videoQuery['v']))
							$videoID = $videoQuery['v'];
					}
					else if(isSet($videoUrl['path']))
						$videoID = $videoUrl['path'];

					
					return '<iframe width="560" height="315" src="https://www.youtube-nocookie.com/embed/' . filter_uri($videoID) . '?rel=0" frameborder="0" allow="encrypted-media" allowfullscreen="true" loading="lazy"></iframe>';

				case "vimeo":
					$videoUrl = parse_url($argument);
					return '<iframe width="560" height="315" src="https://player.vimeo.com/video' . filter_uri($videoUrl['path']) . '" frameborder="0" allowfullscreen="true" loading="lazy"></iframe>';



				case "hr":
					return "<hr>";

				case "anchor":
					return '<span id="' . htmlentities($argument) . '" ></span>';

				default:
					return false;
			}
		}

		function tagEndHTML($tagName)
		{
			switch($tagName)
			{
				case "i":
					return "</i>";

				case "u":
					return "</span>";

				case "b":
					return "</b>";

				case "s":
					return "</span>";

				case "color":
					return "</span>";

				case "size":
					return "</span>";

				case "font":
					return "</span>";

				case "url":
					return "</a>";

				case "iurl":
					return "</a>";

				case "abbr":
					return "</span>";

				case "center":
					return '</div>';

				case "left":
					return '</div>';

				case "right":
					return '</div>';

				case "just":
					return '</div>';

				case "tt":
					return '</span>';

				case "pre":
					return '</pre>';

				case "code":
					return '</blockquote></div>';

				case "quote":
					return '</blockquote></div>';

				case "table":
					return '</table>';

				case "tr":
					return "</tr>";

				case "td":
					return "</td>";


				default:
					return false;
			}
		}
	}
?>