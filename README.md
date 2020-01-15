![Agora Logo](https://agora.evalyn.app/themes/twilight/images/logo.png)
# Agora forum
A basic PHP/MySQL powered forum system with a minimal storage footprint.

Test out or discuss Agora forum here: https://agora.evalyn.app/

## Features
 - A cozy and mobile-responsive default theme.
 - Uncomplicated code (No unexplained regex, things are generally written in a pretty straightforward manner)
 - All primary forum functions supported without Javascript (Javascript still enhances some features)
 - Powerful BBCode parser written specifically for Agora (No regex used. Very straightforward to edit.)
 - A semi-interactive editor for composition that assists users with their BBCode markup.
 - Optional email address confirmation system
 - Private messaging system
 - Lack of feature bloat (or just an excuse as to this list being short)

## To-do
 - Add boards  
 - Add more admin tools and improve existing ones  
 - Search feature

# BBCode reference
This is a reference of the currently implemented bbcode tags. This list should be incorporated into a help menu within Agora itself at some point.

```
[i][/i] - Inner text is italisized.
[b][/b] - Inner text is bolded.
[u][/u] - Inner text is underlined.
[s][/s] - Inner text is crossed out.
[color=CSScolor][/color] - Inner text has the specified CSS color applied to it (CSS colors are things like "Red", "Green" "#EEFF33", "rgba(125, 255, 120, 0.8)", "transparent", etc.).
[size=fontsize][/size] - Sets the text size to a certain size specified (fontsize is just a number with a unit like 'pt' appended. "20pt", "15px", "2em", etc. are all valid).
[url=URL][/url] - Makes the inner elements a link to the specified URL.
[iurl=URL][/iurl] - Inline link. Like the previous tag except that the link opens in the same page. Useful for anchor links.
[anchor=pageanchor] - Creates a page anchor of the specified name. Refer to one in a link (ie. "[iurl=#pageanchor]click to scroll to the anchor[/iurl]") to create links that navigate to specific parts of the post.
[abbr=Text][/abbr] - Inner elements show Text when the mouse is hovered over it.
[center][/center] - Inner elements are centered on the page.
[left][/left] - Inner elements are aligned to the left of the page.
[right][/right] - Inner elements are aligned to the right of the page.
[just][/just] - Inner text is justified.
[tt][/tt] - Sets a monospace font. (Tag name stands for TeleType)
[pre][/pre] - Preformatted text, fully preserves text spacing and sets a monospace font.
[code][/code] - Indicates a block of code, sets a monospace font and preserves spacing.
[quote Name][/quote] - Creates a blockquote of inner text from Name.
[table][/table] - Defines a table. Optional: Add 'border' as a tag argument to create a bordered table.
[tr][/tr] - Table row. Must be a child of table.
[td][/td] - Table column. Must be a child of tr.
[img][/img] - Embeds an image from the url specified by the inner text.
[audio][/audio] - Embeds HTML5 audio from the url specified by the inner text. This must link directly to the audio file, much like an img tag.
[video][/video] - Embeds an HTML5 video from the url specified by the inner text. This must link directly to the video file, much like the img tag.
[youtube][/youtube] - Embeds a video from Youtube using the youtube url specified by the inner text.
[vimeo][/vimeo] - Embeds a video from Vimeo using the vimeo url specified by the inner text.
[hr] - Creates a horizontal line.
[nobbc][/nobbc] - The BBCode parser will skip parsing all text within these tags.
[noparse][/noparse] - Alias of [nobbc].
```