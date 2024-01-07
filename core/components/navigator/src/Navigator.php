<?php

// ===================================================================
// Snippet: Navigator
// ===================================================================
// Version: 0.1
// Date: 2008.12.17
// Author: PMS
// Licence: GPL GNU - Public
// Credit: I used some code from the Wayfinder snippet when figuring out how to do templating.

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
    private $template;


    public function __construct( $params )
    {
        global $modx;
        $this->modx = &$modx;

        // Set allowed values
        $this->relVals = array('up', 'prev', 'next');
        $this->weblinkActionVals = array('skip', 'stop', 'link');
        $this->unpublishedActionVals = array('skip', 'stop', 'link');
        $this->notInMenuActionVals = array('skip', 'stop', 'link');
        $this->placeHolderFieldVals = array('nav.rel');
        $this->rel = 'up';

        // Set default values
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
        $currentId = $this->modx->documentIdentifier;

        // If the snippet has been switched off for this document, don't display it
        if ( in_array( $currentId, $this->offIdArray ) )
        {
            return '';
        }
      
        // Get the content
        $this->template = $this->GetTemplate( $this->templateSource );
        if ( $this->template === FALSE )
        {
            // Could create a template based on type here...
            return '<!--navigator:1-->';
        }      

        while ( true )
        {

            // Get the parent document id
            $parentId = $this->GetParentId( $currentId );

            switch ( $this->rel )
            {
            case 'up':
                $id = $parentId;
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

    private function GetTemplate( $source )
    {
        // based on a version in Wayfinder 2.0... which was...
        // based on version by Doze at http://modxcms.com/forums/index.php/topic,5344.msg41096.html#msg41096
        $template = '';
        if ( $this->modx->getChunk( $source ) != '')
        {
            $template = $this->modx->getChunk( $source );
        }
        else if ( substr($tpl, 0, 6) == '@FILE:' )
        {
            $template = $this->GetFileContents( substr( $source, 6 ) );
        }
        else if ( substr($tpl, 0, 6) == '@CODE:' )
        {
            $template = substr( $source, 6 );
        }
        else
        {
            $template = FALSE;
        }
        return $template;
    }

	private function GetFileContents( $filename )
    {
		// Function written at http://www.nutt.net/2006/07/08/file_get_contents-function-for-php-4/#more-210
		// Returns the contents of file name passed
		if ( ! function_exists( 'file_get_contents' ) )
        {
			$fhandle = fopen( $filename, 'r' );
			$fcontents = fread( $fhandle, filesize( $filename ) );
			fclose( $fhandle );
		}
        else
        {
			$fcontents = file_get_contents( $filename );
		}
		return $fcontents;
	}

    private function SortDocs($a, $b)
    {
        if ( $a['menuindex'] == $b['menuindex'] )
        {
            return 0;
        }
        return ($a['menuindex'] > $b['menuindex']) ? 1 : -1;
    }

    private function GetPlaceHolderFieldsArray( $tpl )
    {
        // Extract the place holders from the document...
        preg_match_all('~\[\+(.*?)\+\]~', $tpl, $matches);
        return array_unique( $matches[1] );
    }

    private function GetTVArray()
    {
        // Gets an array of all template variables
        $table = $this->modx->getFullTableName('site_tmplvars');
        $tvs = $this->modx->db->select('name', $table);
        // TODO: make it so that it only pulls those that apply to the current template
        $dbfields = array();
        while ( $dbfield = $this->modx->db->getRow( $tvs ) )
        {
            $dbfields[] = $dbfield['name'];
        }
        return $dbfields;
    }

    private function GetOutput( $id )
    {
        // Creates the output in the given format.

        // Check that the id is valid
        if ( $id < 0 )
        {
             return '<!--navigator:6-->';
        }
        
        $placeHolderFieldsArray = $this->GetPlaceHolderFieldsArray( $this->template );

        // Handle any navigator placeholders
        $nlPlaceHoldersArray = array();
        $nlPlaceHolderValuesArray = array();
        foreach ( $placeHolderFieldsArray as $item => $field )
        {
            if ( in_array( $field, $this->placeHolderFieldVals ) )
            {
                $nlPlaceHoldersArray[] = '[+' . $field . '+]';
                // $nlPlaceHolderFieldsArray[] = $field;
                // Get the values...
                switch ( $field )
                {
                case 'nav.rel':
                    $nlPlaceHolderValuesArray[] = $this->rel;
                    break;
                default:
                    return '<!--navigator:7-->';
                }
                // Remove this field from the placeHolderFieldsArray
                unset( $placeHolderFieldsArray[$item] );
            }
        }

        $tvArray = $this->GetTVArray();
        // return htmlspecialchars( print_r( $tvArray, true ) );

        $tvPlaceHoldersArray = array();
        $tvPlaceHolderFieldsArray = array();
        foreach ( $placeHolderFieldsArray as $item => $field )
        {
            if ( in_array( $field, $tvArray ) )
            {
                $tvPlaceHoldersArray[] = '[+' . $field . '+]';
                $tvPlaceHolderFieldsArray[] = $field;
                // Remove this field from the placeHolderFieldsArray
                unset( $placeHolderFieldsArray[$item] );
            }
        }
        // return htmlspecialchars( print_r( $tvPlaceHolderFieldsArray, true ) );

        // Template variables
        $tvPlaceHolderValuesArray = array();
        if ( count( $tvPlaceHoldersArray ) > 0 )
        {
            $tvPlaceHolderValuesArray = array_values(
                $this->modx->getTemplateVarOutput(
                    $tvPlaceHolderFieldsArray
                    , $id
                    )
                );
        }
            
        // return htmlspecialchars( print_r( $tvPlaceHolderValuesArray, true ) );

        $docPlaceHoldersArray = array();
        $docPlaceHolderFieldsArray = array();
        foreach ( $placeHolderFieldsArray as $item => $field )
        {
            // if ( in_array( $field, $tvArray ) )
            // {
                $docPlaceHoldersArray[] = '[+' . $field . '+]';
                $docPlaceHolderFieldsArray[] = $field;
                // Remove this field from the placeHolderFieldsArray
                // unset( $placeHolderFieldsArray[$item] );
            // }
        }
        // return htmlspecialchars( print_r( $tvPlaceHolderFieldsArray, true ) );

        // Get the data....
        // Template variables
        $docPlaceHolderValuesArray = array();
        if ( count( $docPlaceHolderFieldsArray ) > 0 )
        {
            $docPlaceHolderValuesArray = array_values(
                $this->modx->getDocument(
                    $id
                    , implode(',',$docPlaceHolderFieldsArray)
                    )
                );
        }

        return str_replace(
            array_merge(
                $nlPlaceHoldersArray
                , $tvPlaceHoldersArray
                , $docPlaceHoldersArray
                )
            , array_merge(
                $nlPlaceHolderValuesArray
                , $tvPlaceHolderValuesArray
                , $docPlaceHolderValuesArray
                )
            , $this->template
            );
      
    }

   private function IsSkipId( $id )
   {
        if ( $id < 0 )
        {
            return TRUE;
        }
        $doc = $this->modx->getDocument( $id, 'type,published,hidemenu' );
        if ( !is_array( $doc ) )
        {
            return TRUE;
        }
        if ( $this->weblinkAction == 'skip' && $doc['type'] == 'reference')
        {
            return TRUE;
        }
        if ( $this->unpublishedAction == 'skip' && ! $doc['published'])
        {
            return TRUE;
        }
        if ( $this->notInMenuAction == 'skip' && $doc['hidemenu'] )
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
        $doc = $this->modx->getDocument( $id, 'type,published,hidemenu' );
        if ( !is_array( $doc ) )
        {
            return TRUE;
        }
        if ( $this->weblinkAction == 'stop' && $doc['type'] == 'reference')
        {
            return TRUE;
        }
        if ( $this->unpublishedAction == 'stop' && ! $doc['published'])
        {
            return TRUE;
        }
        if ( $this->notInMenuAction == 'stop' && $doc['hidemenu'] )
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
        $currentDoc = $this->modx->getDocument( $id, 'parent' );
        return $currentDoc['parent'];
    }

    private function GetFirstChildId( $id )
    {
        // Gets the id of the first child of the document
        // Returns -1 if it can't find one

        $firstChildId = -1;

        // Get the children
        $children = $this->modx->getDocumentChildren( $id, 1, '0', $fields='id, menuindex' );

        // Calculate the number of children
        $nChildren = count( $children );

        if ( $nChildren > 0 )
        {
            // Sort the children by menuindex
            usort( $children, array( 'Navigator', 'SortDocs' ) );

            // Get the id of the first child
            $firstChildId = $children[0]['id'];

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
        $children = $this->modx->getDocumentChildren( $id, 1, '0', $fields='id, menuindex' );

        // Calculate the number of children
        $nChildren = count( $children );

        if ( $nChildren > 0 )
        {
            // Sort the children by menuindex
            usort( $children, array( 'Navigator', 'SortDocs' ) );

            // Get the id of the last child
            $lastChildId = $children[$nChildren-1]['id'];

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
        // $currentDoc = $this->modx->getDocument( $id, 'parent' );
        // $parentId = $currentDoc['parent'];


        // Get the immediate siblings (and the current document)
        $siblings = $this->modx->getDocumentChildren( $parentId, 1, '0', $fields='id, menuindex' );

        // Calculate the number of siblings
        $nSiblings = count( $siblings ) - 1;

        // Sort the children by menuindex
        usort( $siblings, array( 'Navigator', 'SortDocs' ) );

        $currentIndex = -1;
        // Find the current document in the list of siblings
        foreach ( $siblings as  $index => $sibling )
        {
            if ( $sibling['id'] == $id )
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
                $siblingId = $siblings[$currentIndex-1]['id'];
                return $siblingId;
            }
            break;
        case 'next':
            if ( $currentIndex < $nSiblings )
            {
                $siblingId = $siblings[$currentIndex+1]['id'];
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