<?php
/*
===================================================================
Snippet: Navigator
===================================================================
Version: 0.1
Date: 2008.12.17
Author: PMS
Licence: GPL GNU - Public
Credit: I used some code from the Wayfinder snippet when figuring out how to do templating.

===================================================================
About:
===================================================================

This snippet is designed to aid navigation, by providing information about a related page.
Currently, the only relationships this snippet handles are: next, previous (prev) and parent (up)
The snippet uses templating, so you can display whatever information you want about the related pages,
but it's likely to be most useful just for creating links to them.
It's supposed to be an improvement on the PrevJumpNext snippet
in that it has the ability to transcend levels of the menu system.
It doesn't attempt to provide the 'Jump' facility of the PrevJumpNext snippet.

===================================================================
Usage:
===================================================================

To use this snippet you'll have to provide a template for the information you want
to display about the related page.
Within the template you can use:
* modx document variable placeholders [[+id]], [[+pagetitle]], [[+description]], etc...
* Navigator placeholders

Currently, the only navigator placeholder available is  [[+nav.rel]], which returns the relationship with
the document: 'next', 'prev', or 'up'. See the &rel input parameter.

===================================================================
Example templates and snippet calls:
===================================================================

Provide links to related pages using the link element:
LinkChunk: <link rel="[[+nav.rel]]" href="[[~[[+id]]]]"></link>
[[Navigator? &rel=`prev` &template=`LinkChunk`]]
[[Navigator? &rel=`up` &template=`LinkChunk`]]
[[Navigator? &rel=`next` &template=`LinkChunk`]]

Provide links to related pages using a hyperlink:
PrevChunk: <a class="navlink" rel="[[+nav.rel]]" href="[[~[[+id]]]]">Prev</a>
[[Navigator? &rel=`prev` &template=`PrevChunk`]]

======================================================
Configuration Settings
======================================================

&rel [string]
The relationship that the document to be found has to the current document:
'next', 'prev', 'up'
Default: 'up'

&stopIds [string]
A comma separated list of document ids.
If the id of the related document appears in this list, the empty string is returned.
This is useful for specifying starts and ends of sequences of documents
Default: ''

&offIds [string]
A comma separated list of document ids.
If the current document is in this list, the empty string is returned.
Provides a means of turning off the snippet for certain documents
Default: ''

&transcend [number]
Whether or not to transcend levels
when searching for the next and previous documents
Default: 1

&weblinkAction [string]
How to behave when a weblink is encountered.
Possibilities are 'link', 'skip', 'stop'
Default: 'skip'

&unpublishedAction [string]
How to behave when an unpublished document is encountered.
Possibilities are 'link', 'skip', 'stop'
Default: 'skip'

&notInMenuAction [string]
How to behave when a document is encountered that does not appear in the menu.
Possibilities are 'link', 'skip', 'stop'
Default: 'skip'

&template[string]
A template for the output
Use [[+nav.rel]] to return the type of link (prev, next, parent)
Default: ''

===================================================================
Impovements:
===================================================================

This snippet works in a brute force and probably quite inefficient way...
when finding the related document and when skipping unwanted documents.
Thanks to modx's caching mechanism, it's not a big issue however.

By rolling up the logic, which currently involves iteration and multiple modx api calls, 
into a few mysql queries it could probably be made more efficient.
If anyone wants to have a go, feel free

This snippet doesn't currently do anything about phx.

===================================================================
The snippet...
===================================================================
*/
use Navigator\Navigator;

try {
    $navigator = new Navigator($modx, [
        'rel' => $rel
        ,'stopIds' => $stopIds
        ,'offIds' => $offIds
        ,'transcend' => $transcend
        ,'weblinkAction' => $weblinkAction
        ,'unpublishedAction' => $unpublishedAction
        ,'notInMenuAction' => $notInMenuAction
        ,'template' => $template
     ]);

     if (!($navigator instanceof Navigator)){
        $modx->log(xPDO::LOG_LEVEL_ERROR, "Instance of Navigator couldn't be created");
        return '';
     }

     return $navigator->Calculate();
}
catch (\Throwable $t) {
    $modx->log(xPDO::LOG_LEVEL_ERROR, $t->getMessage());
    return '';
}