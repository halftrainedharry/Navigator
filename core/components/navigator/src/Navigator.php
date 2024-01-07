<?php

// ===================================================================
// Snippet: Navigator
// ===================================================================
// Version: 0.1
// Date: 2008.12.17
// Author: PMS
// Licence: GPL GNU - Public
// Credit: I used some code from the Wayfinder snippet when figuring out how to do templating.

namespace Navigator;

use MODX\Revolution\modResource;

class Navigator
{

    // Internal parameters
    private $relVals;
    private $weblinkActionVals;
    private $unpublishedActionVals;
    private $notInMenuActionVals;
    private $placeHolderFieldVals;

    // User supplied parameters
    private $rel;
    private $stopIds;
    private $offIds;
    private $transcend;
    private $weblinkAction;    
    private $unpublishedAction;    
    private $notInMenuAction;    
    private $templateSource;
    private $modx;

    // Derived parameters
    private $stopIdArray;
    private $offIdArray;

    public function __construct(&$modx, $params)
    {
        $this->modx = &$modx;

        // Set allowed values
        $this->relVals = array('up', 'prev', 'next');
        $this->weblinkActionVals = array('skip', 'stop', 'link');
        $this->unpublishedActionVals = array('skip', 'stop', 'link');
        $this->notInMenuActionVals = array('skip', 'stop', 'link');
        $this->placeHolderFieldVals = array('nav.rel');

        // Set default values
        $this->rel = 'up';
        $this->stopIds = '';
        $this->offIds = '';
        $this->transcend = 1;
        $this->weblinkAction = 'skip';    
        $this->unpublishedAction = 'skip';    
        $this->notInMenuAction = 'skip';    
        $this->templateSource = '';

        $this->stopIdArray = array();
        $this->offIdArray = array();

        if ( array_key_exists( 'rel', $params ) )
        {
            if ( in_array( $params['rel'], $this->relVals ) )
            {
                $this->rel = $params['rel'];
            }
        }

        if ( array_key_exists( 'stopIds', $params ) )
        {
            $this->stopIdArray = explode( ',', str_replace(' ','', $params['stopIds'] ) );
        }

        if ( array_key_exists( 'offIds', $params ) )
        {
            $this->offIdArray = explode( ',', str_replace(' ','', $params['offIds'] ) );
        }

        if ( array_key_exists( 'transcend', $params ) )
        {
            if ( $params['transcend'] )
            {
                $this->transcend = 1;
            }
            else
            {
                $this->transcend = 0;
            }
        }

        if ( array_key_exists( 'weblinkAction', $params ) )
        {
            if ( in_array( $params['weblinkAction'], $this->weblinkActionVals ) )
            {
                $this->weblinkAction = $params['weblinkAction'];
            }
        }

        if ( array_key_exists( 'unpublishedAction', $params ) )
        {
            if ( in_array( $params['unpublishedAction'], $this->unpublishedActionVals ) )
            {
                $this->unpublishedAction = $params['unpublishedAction'];
            }
        }

        if ( array_key_exists( 'notInMenuAction', $params ) )
        {
            if ( in_array( $params['notInMenuAction'], $this->notInMenuActionVals ) )
            {
                $this->notInMenuAction = $params['notInMenuAction'];
            }
        }

        if ( array_key_exists( 'template', $params ) )
        {
            $this->templateSource = $params['template'];
        }

    }
   
    public function Calculate( )
    {
   
        // ======================================================
        // Calculation
        // ======================================================

        // Get the current document id
        $currentId = $this->modx->resource->get('id');

        // If the snippet has been switched off for this document, don't display it
        if ( in_array( $currentId, $this->offIdArray ) )
        {
            return '';
        }
      
        // Check if template is defined
        if ( empty($this->templateSource) )
        {
            return '<!--navigator:1-->';
        }      

        while ( true )
        {

            switch ( $this->rel )
            {
            case 'up':
                $id = $this->GetParentId( $currentId );
                break;
            case 'prev':
                $id = $this->GetPreviousDocId( $currentId );      
                break;
            case 'next':
                $id = $this->GetNextDocId( $currentId );      
                break;
            default:
                return '<!--navigator:3-->';
            }
          
            if ( $id < 0 )
            {
                return '<!--navigator:4-->';
            }

            if ( $this->IsStopId( $id ) )
            {
                return '';
            }

            if ( $this->IsSkipId( $id ) )
            {
                $currentId = $id;
            }
            else
            {
                break;
            }
        }
      

        // Return the parent's sibling's id
        return $this->GetOutput( $id );      
    }

    private function GetOutput( $id )
    {
        // Creates the output in the given format.

        // Check that the id is valid
        if ( $id < 0 )
        {
             return '<!--navigator:6-->';
        }

        $doc = $this->modx->getObject(modResource::class, $id);
        if (!doc){
            return '<!--navigator:7-->';
        }
        $fields = $doc->toArray();
        $fields['nav.rel'] = $this->rel;

        return $this->modx->getChunk($this->templateSource, $fields);
    }

   private function IsSkipId( $id )
   {
        if ( $id < 0 )
        {
            return TRUE;
        }
        $doc = $this->modx->getObject(modResource::class, $id);
        if (!$doc)
        {
            return TRUE;
        }
        if ( $this->weblinkAction == 'skip' && ($doc->get('type') == 'reference' || $doc->get('class_key') == 'MODX\\Revolution\\modWebLink'))
        {
            return TRUE;
        }
        if ( $this->unpublishedAction == 'skip' && ! $doc->get('published'))
        {
            return TRUE;
        }
        if ( $this->notInMenuAction == 'skip' && $doc->get('hidemenu') )
        {
            return TRUE;
        }
        return FALSE;
        
    }

    private function IsStopId( $id )
    {
        if ( $id < 0 )
        {
            return TRUE;
        }
        if ( in_array( $id, $this->stopIdArray ) )
        {
            return TRUE;
        }
        $doc = $this->modx->getObject(modResource::class, $id);
        if (!$doc)
        {
            return TRUE;
        }
        if ( $this->weblinkAction == 'stop' && ($doc->get('type') == 'reference' || $doc->get('class_key') == 'MODX\\Revolution\\modWebLink'))
        {
            return TRUE;
        }
        if ( $this->unpublishedAction == 'stop' && ! $doc->get('published'))
        {
            return TRUE;
        }
        if ( $this->notInMenuAction == 'stop' && $doc->get('hidemenu') )
        {
            return TRUE;
        }
        return FALSE;
        
    }

    private function GetParentId( $id )
    {
        // Gets the id of the parent.
        // If the document is the root, and so has no parent, -1 is returned
        if ( $id == 0 )
        {
            return -1;
        }

        $q = $this->modx->newQuery(modResource::class, $id);
        $q->select('parent');
        $parentId = $this->modx->getValue($q->prepare());
        return $parentId;
    }

    private function GetFirstChildId( $id )
    {
        // Gets the id of the first child of the document
        // Returns -1 if it can't find one

        $firstChildId = -1;

        // Get the children
        $q = $this->modx->newQuery(modResource::class, ['parent' => $id, 'published' => true, 'deleted' => false]);
        $q->select('id');
        $q->sortby('menuindex','ASC');
        $q->sortby('id','ASC');
        $q->limit(1);

        $child = $this->modx->getObject(modResource::class, $q);

        if ($child)
        {
            // Get the id of the first child
            $firstChildId = $child->get('id');

            return $firstChildId;
        }

        return $firstChildId;

    }

    private function GetLastChildId( $id )
    {
        // Gets the id of the last child of the document
        // Returns -1 if it can't find one

        $lastChildId = -1;

        // Get the children
        $q = $this->modx->newQuery(modResource::class, ['parent' => $id, 'published' => true, 'deleted' => false]);
        $q->select('id');
        $q->sortby('menuindex','DESC');
        $q->sortby('id','DESC');
        $q->limit(1);

        $child = $this->modx->getObject(modResource::class, $q);

        if ($child)
        {
            // Get the id of the last child
            $lastChildId = $child->get('id');

            return $lastChildId;
        }

        return $lastChildId;

    }

    private function GetSiblingId( $id )
    {
        // Gets the next sibling.
        // Returns -1 if one doesn't exist

        $siblingId = -1;

        // If the document is the document root, then it has no siblings
        if ( $id == 0 )
        {
            return -1;
        }

        // Get the parent document id
        $parentId = $this->GetParentId( $id );

        // Get the immediate siblings (and the current document)
        $q = $this->modx->newQuery(modResource::class, ['parent' => $parentId, 'published' => true, 'deleted' => false]);
        $q->select('id');
        $q->sortby('menuindex','ASC');
        $q->sortby('id','ASC');

        $siblings = $this->modx->getCollection(modResource::class, $q);

        // Calculate the number of siblings
        $nSiblings = count( $siblings ) - 1;

        $currentIndex = -1;
        // Find the current document in the list of siblings
        foreach ( $siblings as  $index => $sibling )
        {
            if ( $sibling->get('id') == $id )
            {
                $currentIndex = $index;
                break;
            }
        }
        if ($currentIndex == -1)
        {
            return $siblingId;
        }

        switch ( $this->rel )
        {
        case 'prev':
            if ( $currentIndex > 0 )
            {
                $siblingId = $siblings[$currentIndex-1]->get('id');
                return $siblingId;
            }
            break;
        case 'next':
            if ( $currentIndex < $nSiblings )
            {
                $siblingId = $siblings[$currentIndex+1]->get('id');
                return $siblingId;
            }
            break;
        default:
            return $siblingId;
        }

        return $siblingId;
    }

    private function GetPreviousDocId( $id )
    {
        if ( $this->transcend )
        {
            // If the current document has a previous sibling, then get its last child

            // Check if the current document has a previous sibling
            $siblingId = $this->GetSiblingId( $id );

            // If it has a sibling...
            while ( $siblingId >= 0 )
            {
                // Get the last child of the document
                $lastChildId = $this->GetLastChildId( $siblingId );

                // If it doesn't have a child, then stop here
                if ( $lastChildId < 0 )
                {
                    return $siblingId;
                }

                // Go down the tree
                $siblingId = $lastChildId;

            }

            // The current document doesn't have a previous sibling, so get its parent.
            $parentId = $this->GetParentId( $id );

            if ( $parentId >= 0)
            {
                return $parentId;
            }

            return -1;         
        }
        else
        {

            // A sibling was required. Get the sibling
            $siblingId = $this->GetSiblingId( $id );

            // If the sibling has been found, then create the link
            if ( $siblingId >= 0 )
            {
                return $siblingId;
            }

            return -1;
        }

        return -1;
    }

    private function GetNextDocId( $id )
    {
        if ( $this->transcend )
        {
            // If the current document has children, then get the first child.
            $firstChildId = $this->GetFirstChildId( $id );

            if ( $firstChildId >= 0 )
            {
                // Return the first page
                return $firstChildId;
            }

            // otherwise, try to get the next sibling.
            $siblingId = $this->GetSiblingId( $id );

            if ( $siblingId >= 0)
            {
                // Return the first page
                return $siblingId;
            }

            // If there is no next sibling, get the parent's sibling.
            $parentId = $this->GetParentId( $id );

            // Check if the parent is valid
            while ( $parentId >= 0 )
            {
                $parentsSiblingId = $this->GetSiblingId( $parentId );

                if ( $parentsSiblingId >= 0 )
                {
                    // Return the parent's sibling's id
                    return $parentsSiblingId;
                }

                // Repeat the last step until completion
                $parentId = $this->GetParentId( $parentId );
            }      

            return -1;

        }
        else
        {

            // A sibling was required. Get the sibling
            $siblingId = $this->GetSiblingId( $id );

            // If the sibling has been found, then create the link
            if ( $siblingId >= 0 )
            {
                return $siblingId;
            }

            return -1;
        }

        return -1;
    }

}
?>