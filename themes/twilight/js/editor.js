var _editorCreated = false;

function createEditorTrayItem(name, title, description, iconPath)
{
	let tray = document.getElementById("editor-tray");

	let button = document.createElement("div");
	button.actionName = name;
	button.actionDescription = description;
	button.title = title;
	button.addEventListener('click', editorButtonPress);
	button.addEventListener('mouseenter', editorButtonHover);
	button.classList.add("editor-button");

	let image = document.createElement("img");
	image.actionName = name;
	image.actionDescription = description;
	image.title = title;
	image.alt = title;
	image.src = iconPath;

	button.appendChild(image);
	tray.appendChild(button);

	return button;
}

function createTrayButtons()
{
	if(_editorCreated)
		return;

	_editorCreated = true;

	createEditorTrayItem("bold",			"Bold",				"Make text bold.", "./themes/twilight/images/open-iconic-master/svg/bold.svg");
	createEditorTrayItem("italic",			"Italic",			"Make text italicized", "./themes/twilight/images/open-iconic-master/svg/italic.svg");
	createEditorTrayItem("underline",		"Underline",		"Make text underlined", "./themes/twilight/images/open-iconic-master/svg/underline.svg");
	createEditorTrayItem("strikethrough",	"Strikethrough",	"Strike-through your text", "./themes/twilight/images/open-iconic-master/svg/minus.svg");
	createEditorTrayItem("align-left",		"Align: Left",		"Make text left-aligned", "./themes/twilight/images/open-iconic-master/svg/align-left.svg");
	createEditorTrayItem("align-center",	"Align: Center",	"Make text center-aligned", "./themes/twilight/images/open-iconic-master/svg/align-center.svg");
	createEditorTrayItem("align-right",		"Align: Right",		"Make text right-aligned", "./themes/twilight/images/open-iconic-master/svg/align-right.svg");
	createEditorTrayItem("justify",			"Justify",			"Justify this text (Lines of text which wrap to the next line will be spaced so that the text aligns to the beginning and end of the block)", "./themes/twilight/images/open-iconic-master/svg/justify-left.svg");
	createEditorTrayItem("color",			"Text Color",		"Set the text color", "./themes/twilight/images/open-iconic-master/svg/cloudy.svg");
	createEditorTrayItem("size",			"Text Size",		"Set the text size", "./themes/twilight/images/open-iconic-master/svg/resize-height.svg");
	createEditorTrayItem("font",			"Text Font",		"Set the text font name", "./themes/twilight/images/open-iconic-master/svg/text.svg");
	createEditorTrayItem("hyperlink",		"Hyperlink",		"Make the selected text or elements a hyperlink", "./themes/twilight/images/open-iconic-master/svg/external-link.svg");
	createEditorTrayItem("image",			"Embed Image",		"Embed an image into your post, you will have to supply a URL directly to the image", "./themes/twilight/images/open-iconic-master/svg/image.svg");
	createEditorTrayItem("audio",			"Embed Audio",		"Embed audio into your post, you will have to supply a URL directly to an audio file", "./themes/twilight/images/open-iconic-master/svg/musical-note.svg");
	createEditorTrayItem("video",			"Embed Video",		"Embed a video into your post, you will have to supply a URL directly to a video file", "./themes/twilight/images/open-iconic-master/svg/video.svg");
	createEditorTrayItem("youtube",			"Youtube",			"Embed a youtube video into your post, you will have to supply the normal video URL (not the shortened URL)", "./themes/twilight/images/open-iconic-master/svg/monitor.svg");
	createEditorTrayItem("vimeo",			"Vimeo",			"Embed a vimeo video into your post, you will have to supply the normal video URL", "./themes/twilight/images/open-iconic-master/svg/laptop.svg");
	createEditorTrayItem("inline-hyperlink","Inline Hyperlink",	"Make the selected text or elements a hyperlink that doesn't open a new tab, useful for page anchors", "./themes/twilight/images/open-iconic-master/svg/link-intact.svg");
	createEditorTrayItem("anchor",			"Page Anchor",		"Create a page anchor that the user will scroll to automatically if they click a link with this anchor specified", "./themes/twilight/images/open-iconic-master/svg/tag.svg");
	createEditorTrayItem("title-text",		"Title Text",		"Make the selected text or elements display text when hovered with the mouse", "./themes/twilight/images/open-iconic-master/svg/comment-square.svg");
	createEditorTrayItem("preformatted",	"Preformatted Text","Make this text preformatted (It will use a monospace font and preserve excess whitespace, useful for ascii art and code formatting)", "./themes/twilight/images/open-iconic-master/svg/copywriting.svg");
	createEditorTrayItem("code",			"Code Block",		"Make this text a code block (Indicates a code block, uses a monospace font, preserves whitespace, and disables line wrapping)", "./themes/twilight/images/open-iconic-master/svg/script.svg");
	createEditorTrayItem("quote",			"Quote",			"Create a blockquote (Tip: You can click 'Quote/Reply' under any post to automatically generate a quote of that post)", "./themes/twilight/images/open-iconic-master/svg/double-quote-serif-left.svg");
	createEditorTrayItem("table",			"Create Table",		"Initialize a table with two rows and two coumns", "./themes/twilight/images/open-iconic-master/svg/grid-two-up.svg");
	createEditorTrayItem("table-row",		"Table Row",		"Insert a new row with column into a table (This can only work inside a [table])", "./themes/twilight/images/open-iconic-master/svg/collapse-down.svg");
	createEditorTrayItem("table-column",	"Table Column",		"Insert a column into a row (This can only work inside a [tr])", "./themes/twilight/images/open-iconic-master/svg/collapse-right.svg");
	createEditorTrayItem("horizontal-line",	"Horizontal Line",	"Add a horizontal line into your post", "./themes/twilight/images/open-iconic-master/svg/menu.svg");
	createEditorTrayItem("no-parse",		"Skip Parsing",		"Skip bbcode parsing in all selected text (Useful for typing bbcode in your post literally)", "./themes/twilight/images/open-iconic-master/svg/terminal.svg");
}

function editorWarning(warningText)
{
	let editorWarnings = document.getElementById("editor-warnings");

	editorWarnings.innerHTML = warningText;
}

function editorButtonHover(event)
{
	let description = event.target.actionDescription;
	document.getElementById("editor-warnings").innerHTML = description;
}

function editorButtonPress(event)
{
	let actionName = event.target.actionName;

	let textBox = document.getElementById("replytext");
	const cursorStart = textBox.selectionStart;
	const cursorEnd = textBox.selectionEnd;
	let newCursorStart;
	let newCursorEnd;

	const originalText = textBox.value;
	let newText;

	let openTag;
	let closeTag;

	switch(actionName)
	{
		case "bold":
			openTag = "[b]";
			closeTag = "[/b]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length;
			newCursorEnd = newCursorStart + (cursorEnd - cursorStart);

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "italic":
			openTag = "[i]";
			closeTag = "[/i]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length;
			newCursorEnd = newCursorStart + (cursorEnd - cursorStart);

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "underline":
			openTag = "[u]";
			closeTag = "[/u]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length;
			newCursorEnd = newCursorStart + (cursorEnd - cursorStart);

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "strikethrough":
			openTag = "[s]";
			closeTag = "[/s]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length;
			newCursorEnd = newCursorStart + (cursorEnd - cursorStart);

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "color":
			openTag = "[color=orange]";
			closeTag = "[/color]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length - 7;
			newCursorEnd = newCursorStart + 6;

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "size":
			openTag = "[size=15pt]";
			closeTag = "[/size]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length - 5;
			newCursorEnd = newCursorStart + 2;

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "font":
			openTag = "[font=serif]";
			closeTag = "[/font]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length - 6;
			newCursorEnd = newCursorStart + 5;

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "hyperlink":
			openTag = "[url=http://]";
			closeTag = "[/url]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length - 1;
			newCursorEnd = newCursorStart;

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "inline-hyperlink":
			openTag = "[iurl=#anchorName]";
			closeTag = "[/iurl]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length - 11;
			newCursorEnd = newCursorStart + 10;

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "anchor":
			openTag = "[anchor=anchorName]";
			closeTag = "";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length - 11;
			newCursorEnd = newCursorStart + 10;

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "title-text":
			openTag = "[abbr=Title text]";
			closeTag = "[/abbr]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length - 11;
			newCursorEnd = newCursorStart + 10;

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "align-center":
			openTag = "[center]";
			closeTag = "[/center]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length;
			newCursorEnd = newCursorStart;

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case"align-left":
			openTag = "[left]";
			closeTag = "[/left]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length;
			newCursorEnd = newCursorStart;

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "align-right":
			openTag = "[right]";
			closeTag = "[/right]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length;
			newCursorEnd = newCursorStart;

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "justify":
			openTag = "[just]";
			closeTag = "[/just]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length;
			newCursorEnd = newCursorStart;

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "preformatted":
			openTag = "[pre]";
			closeTag = "[/pre]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length;
			newCursorEnd = newCursorStart;

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "code":
			openTag = "[code]";
			closeTag = "[/code]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length;
			newCursorEnd = newCursorStart;

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "quote":
			openTag = "[quote=Author]";
			closeTag = "[/quote]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length - 7;
			newCursorEnd = newCursorStart + 6;

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "table":
			openTag = "[table]\n[tr][td]\n";
			closeTag = "\n[/td][td]\n\n[/td][/tr][tr][td]\n\n[/td][td]\n\n[/td][/tr]\n[/table]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length;
			newCursorEnd = newCursorStart;

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "table-row":
			openTag = "[tr][td]\n";
			closeTag = "\n[/td][/tr]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length;
			newCursorEnd = newCursorStart;

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "table-column":
			openTag = "[td]\n";
			closeTag = "\n[/td]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length;
			newCursorEnd = newCursorStart;

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "image":
			openTag = "[image]http://example.com/image.png";
			closeTag = "[/image]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length - 28;
			newCursorEnd = newCursorStart + 28;

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "audio":
			openTag = "[audio]http://example.com/audio.mp3";
			closeTag = "[/audio]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length - 28;
			newCursorEnd = newCursorStart + 28;

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "video":
			openTag = "[video]http://example.com/video.mp4";
			closeTag = "[/video]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length - 28;
			newCursorEnd = newCursorStart + 28;

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "youtube":
			openTag = "[youtube]https://youtube.com/watch?v=...";
			closeTag = "[/youtube]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length - 28;
			newCursorEnd = newCursorStart + 28;

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "vimeo":
			openTag = "[vimeo]https://vimeo.com/...";
			closeTag = "[/vimeo]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length - 28;
			newCursorEnd = newCursorStart + 28;

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "horizontal-line":
			openTag = "[hr]";
			closeTag = "";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length - 28;
			newCursorEnd = newCursorStart + 28;

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;

		case "no-parse":
			openTag = "[nobbc]";
			closeTag = "[/nobbc]";

			newText = originalText.substring(0, cursorStart);

			newCursorStart = cursorStart + openTag.length - 28;
			newCursorEnd = newCursorStart + 28;

			newText += openTag + originalText.substring(cursorStart, cursorEnd) + closeTag + originalText.substring(cursorEnd);
			break;
	}

	if(newText)
	{
		textBox.value = newText;

		if(newCursorStart && newCursorEnd)
		{
			textBox.setSelectionRange(newCursorStart, newCursorEnd);
		}

		textBox.focus();
	}
}

window.addEventListener('DOMContentLoaded', function()
{
	createTrayButtons();
});

window.addEventListener('load', function()
{
	createTrayButtons();
});