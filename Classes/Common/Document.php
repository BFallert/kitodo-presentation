<?php
namespace Kitodo\Dlf\Common;

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Ubl\Iiif\Presentation\Common\Model\Resources\IiifResourceInterface;
use Ubl\Iiif\Tools\IiifHelper;

/**
 * Document class for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author Henrik Lochmann <dev@mentalmotive.com>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 * @abstract
 */
abstract class Document {
    /**
     * This holds the PID for the configuration
     *
     * @var integer
     * @access protected
     */
    protected $cPid = 0;

    /**
     * The extension key
     *
     * @var string
     * @access public
     */
    public static $extKey = 'dlf';

    /**
     * This holds the configuration for all supported metadata encodings
     * @see loadFormats()
     *
     * @var array
     * @access protected
     */
    protected $formats = [
        'OAI' => [
            'rootElement' => 'OAI-PMH',
            'namespaceURI' => 'http://www.openarchives.org/OAI/2.0/',
        ],
        'METS' => [
            'rootElement' => 'mets',
            'namespaceURI' => 'http://www.loc.gov/METS/',
        ],
        'XLINK' => [
            'rootElement' => 'xlink',
            'namespaceURI' => 'http://www.w3.org/1999/xlink',
        ]
    ];

    /**
     * Are there any fulltext files available? This also includes IIIF text annotations
     * with motivation 'painting' if Kitodo.Presentation is configured to store text
     * annotations as fulltext.
     *
     * @var boolean
     * @access protected
     */
    protected $hasFulltext = FALSE;

    /**
     * Last searched logical and physical page
     *
     * @var array
     * @access protected
     */
    protected $lastSearchedPhysicalPage = ['logicalPage' => NULL, 'physicalPage' => NULL];

    /**
     * This holds the documents location
     *
     * @var string
     * @access protected
     */
    protected $location = '';

    /**
     * This holds the logical units
     *
     * @var array
     * @access protected
     */
    protected $logicalUnits = [];

    /**
     * This holds the documents' parsed metadata array with their corresponding
     * structMap//div's ID (METS) or Range / Manifest / Sequence ID (IIIF) as array key
     *
     * @var array
     * @access protected
     */
    protected $metadataArray = [];

    /**
     * Is the metadata array loaded?
     * @see $metadataArray
     *
     * @var boolean
     * @access protected
     */
    protected $metadataArrayLoaded = FALSE;

    /**
     * The holds the total number of pages
     *
     * @var integer
     * @access protected
     */
    protected $numPages = 0;

    /**
     * This holds the UID of the parent document or zero if not multi-volumed
     *
     * @var integer
     * @access protected
     */
    protected $parentId = 0;

    /**
     * This holds the physical structure
     *
     * @var array
     * @access protected
     */
    protected $physicalStructure = [];

    /**
     * This holds the physical structure metadata
     *
     * @var array
     * @access protected
     */
    protected $physicalStructureInfo = [];

    /**
     * Is the physical structure loaded?
     * @see $physicalStructure
     *
     * @var boolean
     * @access protected
     */
    protected $physicalStructureLoaded = FALSE;

    /**
     * This holds the PID of the document or zero if not in database
     *
     * @var integer
     * @access protected
     */
    protected $pid = 0;

    /**
     * This holds the documents' raw text pages with their corresponding
     * structMap//div's ID (METS) or Range / Manifest / Sequence ID (IIIF) as array key
     *
     * @var array
     * @access protected
     */
    protected $rawTextArray = [];

    /**
     * Is the document instantiated successfully?
     *
     * @var boolean
     * @access protected
     */
    protected $ready = FALSE;

    /**
     * The METS file's / IIIF manifest's record identifier
     *
     * @var string
     * @access protected
     */
    protected $recordId;

    /**
     * This holds the singleton object of the document
     *
     * @var array (\Kitodo\Dlf\Common\Document)
     * @static
     * @access protected
     */
    protected static $registry = [];

    /**
     * This holds the UID of the root document or zero if not multi-volumed
     *
     * @var integer
     * @access protected
     */
    protected $rootId = 0;

    /**
     * Is the root id loaded?
     * @see $rootId
     *
     * @var boolean
     * @access protected
     */
    protected $rootIdLoaded = FALSE;

    /**
     * This holds the smLinks between logical and physical structMap
     *
     * @var array
     * @access protected
     */
    protected $smLinks = ['l2p' => [], 'p2l' => []];

    /**
     * Are the smLinks loaded?
     * @see $smLinks
     *
     * @var boolean
     * @access protected
     */
    protected $smLinksLoaded = FALSE;

    /**
     * This holds the logical structure
     *
     * @var array
     * @access protected
     */
    protected $tableOfContents = [];

    /**
     * Is the table of contents loaded?
     * @see $tableOfContents
     *
     * @var boolean
     * @access protected
     */
    protected $tableOfContentsLoaded = FALSE;

    /**
     * This holds the document's thumbnail location.
     *
     * @var string
     * @access protected
     */
    protected $thumbnail = '';

    /**
     * Is the document's thumbnail location loaded?
     * @see $thumbnail
     *
     * @var boolean
     * @access protected
     */
    protected $thumbnailLoaded = FALSE;

    /**
     * This holds the toplevel structure's @ID (METS) or the manifest's @id (IIIF).
     *
     * @var string
     * @access protected
     */
    protected $toplevelId = '';

    /**
     * This holds the UID or the URL of the document
     *
     * @var mixed
     * @access protected
     */
    protected $uid = 0;

    /**
     * This holds the whole XML file as \SimpleXMLElement object
     *
     * @var \SimpleXMLElement
     * @access protected
     */
    protected $xml;

    /**
     * This clears the static registry to prevent memory exhaustion
     *
     * @access public
     *
     * @static
     *
     * @return void
     */
    public static function clearRegistry() {
        // Reset registry array.
        self::$registry = [];
    }

    /**
     * This ensures that the recordId, if existent, is retrieved from the document.
     *
     * @access protected
     *
     * @abstract
     *
     * @param integer $pid: ID of the configuration page with the recordId config
     *
     */
    protected abstract function establishRecordId($pid);

    /**
     * Source document PHP object which is represented by a Document instance
     *
     * @access protected
     *
     * @abstract
     *
     * @return \SimpleXMLElement|IiifResourceInterface An PHP object representation of
     * the current document. SimpleXMLElement for METS, IiifResourceInterface for IIIF
     */
    protected abstract function getDocument();

    /**
     * This gets the location of a downloadable file for a physical page or track
     *
     * @access public
     *
     * @abstract
     *
     * @param string $id: The @ID attribute of the file node (METS) or the @id property of the IIIF resource
     *
     * @return string    The file's location as URL
     */
    public abstract function getDownloadLocation($id);

    /**
     * This gets the location of a file representing a physical page or track
     *
     * @access public
     *
     * @abstract
     *
     * @param string $id: The @ID attribute of the file node (METS) or the @id property of the IIIF resource
     *
     * @return string The file's location as URL
     */
    public abstract function getFileLocation($id);

    /**
     * This gets the MIME type of a file representing a physical page or track
     *
     * @access public
     *
     * @abstract
     *
     * @param string $id: The @ID attribute of the file node
     *
     * @return string The file's MIME type
     */
    public abstract function getFileMimeType($id);

    /**
     * This is a singleton class, thus an instance must be created by this method
     *
     * @access public
     *
     * @static
     *
     * @param mixed $uid: The unique identifier of the document to parse, the URL of XML file or the IRI of the IIIF resource
     * @param integer $pid: If > 0, then only document with this PID gets loaded
     * @param boolean $forceReload: Force reloading the document instead of returning the cached instance
     *
     * @return \Kitodo\Dlf\Common\Document Instance of this class, either MetsDocument or IiifManifest
     */
    public static function &getInstance($uid, $pid = 0, $forceReload = FALSE) {
        // Sanitize input.
        $pid = max(intval($pid), 0);
        if (!$forceReload) {
            $regObj = md5($uid);
            if (is_object(self::$registry[$regObj])
                && self::$registry[$regObj] instanceof self) {
                // Check if instance has given PID.
                if (!$pid
                    || !self::$registry[$regObj]->pid
                    || $pid == self::$registry[$regObj]->pid) {
                    // Return singleton instance if available.
                    return self::$registry[$regObj];
                }
            } else {
                // Check the user's session...
                $sessionData = Helper::loadFromSession(get_called_class());
                if (is_object($sessionData[$regObj])
                    && $sessionData[$regObj] instanceof self) {
                    // Check if instance has given PID.
                    if (!$pid
                        || !$sessionData[$regObj]->pid
                        || $pid == $sessionData[$regObj]->pid) {
                        // ...and restore registry.
                        self::$registry[$regObj] = $sessionData[$regObj];
                        return self::$registry[$regObj];
                    }
                }
            }
        }
        // Create new instance depending on format (METS or IIIF) ...
        $documentFormat = null;
        $xml = null;
        $iiif = null;
        // Try to get document format from database
        if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid)) {
            $whereClause = 'tx_dlf_documents.uid='.intval($uid).Helper::whereClause('tx_dlf_documents');
            if ($pid) {
                $whereClause .= ' AND tx_dlf_documents.pid='.intval($pid);
            }
            $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'tx_dlf_documents.location AS location,tx_dlf_documents.document_format AS document_format',
                'tx_dlf_documents',
                $whereClause,
                '',
                '',
                '1'
                );

            if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0) {
                for ($i = 0, $j = $GLOBALS['TYPO3_DB']->sql_num_rows($result); $i < $j; $i++) {
                    $resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
                    $documentFormat = $resArray['document_format'];
                }
            }

        } else {
            // Get document format from content of remote document
            // Cast to string for safety reasons.
            $location = (string) $uid;
            // Try to load a file from the url
            if (\TYPO3\CMS\Core\Utility\GeneralUtility::isValidUrl($location)) {
                // Load extension configuration
                $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dlf']);
                // Set user-agent to identify self when fetching XML data.
                if (!empty($extConf['useragent'])) {
                    @ini_set('user_agent', $extConf['useragent']);
                }
                $content = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($location);
                // TODO use single place to load xml
                // Turn off libxml's error logging.
                $libxmlErrors = libxml_use_internal_errors(TRUE);
                // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
                $previousValueOfEntityLoader = libxml_disable_entity_loader(TRUE);
                // Try to load XML from file.
                $xml = simplexml_load_string($content);
                // reset entity loader setting
                libxml_disable_entity_loader($previousValueOfEntityLoader);
                // Reset libxml's error logging.
                libxml_use_internal_errors($libxmlErrors);
                if ($xml !== false) {
                    /* @var $xml \SimpleXMLElement */
                    $xml->registerXPathNamespace('mets', 'http://www.loc.gov/METS/');
                    $xpathResult = $xml->xpath('//mets:mets');
                    $documentFormat = ($xpathResult !== false && count($xpathResult)>0) ? 'METS' : null;
                } else {
                    // Try to load file as IIIF resource instead.
                    $contentAsJsonArray = json_decode($content, true);
                    if ($contentAsJsonArray !== null) {
                        // Load plugin configuration.
                        $conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::$extKey]);
                        IiifHelper::setUrlReader(IiifUrlReader::getInstance());
                        IiifHelper::setMaxThumbnailHeight($conf['iiifThumbnailHeight']);
                        IiifHelper::setMaxThumbnailWidth($conf['iiifThumbnailWidth']);
                        $iiif = IiifHelper::loadIiifResource($contentAsJsonArray);
                        if ($iiif instanceof IiifResourceInterface) {
                            $documentFormat = 'IIIF';
                        }
                    }
                }
            }
        }
        // Sanitize input.
        $pid = max(intval($pid), 0);
        if ($documentFormat == 'METS') {
            $instance = new MetsDocument($uid, $pid, $xml);
        } elseif ($documentFormat == 'IIIF') {
            $instance = new IiifManifest($uid, $pid, $iiif);
        }
        // Save instance to registry.
        if ($instance->ready) {
            self::$registry[md5($instance->uid)] = $instance;
            if ($instance->uid != $instance->location) {
                self::$registry[md5($instance->location)] = $instance;
            }
            // Load extension configuration
            $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dlf']);
            // Save registry to session if caching is enabled.
            if (!empty($extConf['caching'])) {
                Helper::saveToSession(self::$registry, get_class($instance));
            }
        }
        // Return new instance.
        return $instance;
    }

    /**
     * This gets details about a logical structure element
     *
     * @access public
     *
     * @abstract
     *
     * @param string $id: The @ID attribute of the logical structure node (METS) or
     * the @id property of the Manifest / Range (IIIF)
     * @param boolean $recursive: Whether to include the child elements / resources
     *
     * @return array Array of the element's id, label, type and physical page indexes/mptr link
     */
    public abstract function getLogicalStructure($id, $recursive = FALSE);

    /**
     * This extracts all the metadata for a logical structure node
     *
     * @access public
     *
     * @abstract
     *
     * @param string $id: The @ID attribute of the logical structure node (METS) or the @id property
     * of the Manifest / Range (IIIF)
     * @param integer $cPid: The PID for the metadata definitions
     *                       (defaults to $this->cPid or $this->pid)
     *
     * @return array The logical structure node's / the IIIF resource's parsed metadata array
     */
    public abstract function getMetadata($id, $cPid = 0);

    /**
     * This returns the first corresponding physical page number of a given logical page label
     *
     * @access public
     *
     * @param string $logicalPage: The label (or a part of the label) of the logical page
     *
     * @return integer The physical page number
     */
    public function getPhysicalPage($logicalPage) {
        if (!empty($this->lastSearchedPhysicalPage['logicalPage'])
            && $this->lastSearchedPhysicalPage['logicalPage'] == $logicalPage) {
            return $this->lastSearchedPhysicalPage['physicalPage'];
        } else {
            $physicalPage = 0;
            foreach ($this->physicalStructureInfo as $page) {
                if (strpos($page['orderlabel'], $logicalPage) !== FALSE) {
                    $this->lastSearchedPhysicalPage['logicalPage'] = $logicalPage;
                    $this->lastSearchedPhysicalPage['physicalPage'] = $physicalPage;
                    return $physicalPage;
                }
                $physicalPage++;
            }
        }
        return 1;
    }

    /**
     * This extracts the raw text for a physical structure node / IIIF Manifest / Canvas. Text might be
     * given as ALTO for METS or as annotations or ALTO for IIIF resources. If IIIF plain text annotations
     * with the motivation "painting" should be treated as full text representations, the extension has to be
     * configured accordingly.
     *
     * @access public
     *
     * @abstract
     *
     * @param string $id: The @ID attribute of the physical structure node (METS) or the @id property
     * of the Manifest / Range (IIIF)
     *
     * @return string The physical structure node's / IIIF resource's raw text
     */
    public abstract function getRawText($id);

    /**
     * This extracts the raw text for a physical structure node / IIIF Manifest / Canvas from an
     * XML fulltext representation (currently only ALTO). For IIIF manifests, ALTO documents have
     * to be given in the Canvas' / Manifest's "seeAlso" property.
     *
     * @param string $id: The @ID attribute of the physical structure node (METS) or the @id property
     * of the Manifest / Range (IIIF)
     *
     * @return string The physical structure node's / IIIF resource's raw text from XML
     */
    protected function getRawTextFromXml($id) {
        $rawText = '';
        // Load available text formats, ...
        $this->loadFormats();
        // ... physical structure ...
        $this->_getPhysicalStructure();
        // ... and extension configuration.
        $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::$extKey]);
        if (!empty($this->physicalStructureInfo[$id])) {
            // Get fulltext file.
            $file = $this->getFileLocation($this->physicalStructureInfo[$id]['files'][$extConf['fileGrpFulltext']]);
            // Turn off libxml's error logging.
            $libxmlErrors = libxml_use_internal_errors(TRUE);
            // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept.
            $previousValueOfEntityLoader = libxml_disable_entity_loader(TRUE);
            // Load XML from file.
            $rawTextXml = simplexml_load_string(\TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($file));
            // Reset entity loader setting.
            libxml_disable_entity_loader($previousValueOfEntityLoader);
            // Reset libxml's error logging.
            libxml_use_internal_errors($libxmlErrors);
            // Get the root element's name as text format.
            $textFormat = strtoupper($rawTextXml->getName());
        } else {
            Helper::devLog('Invalid structure node @ID "'.$id.'"', DEVLOG_SEVERITY_WARNING);
            return $rawText;
        }
        // Is this text format supported?
        if (!empty($this->formats[$textFormat])) {
            if (!empty($this->formats[$textFormat]['class'])) {
                $class = $this->formats[$textFormat]['class'];
                // Get the raw text from class.
                if (class_exists($class)
                    && ($obj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($class)) instanceof FulltextInterface) {
                    $rawText = $obj->getRawText($rawTextXml);
                    $this->rawTextArray[$id] = $rawText;
                } else {
                    Helper::devLog('Invalid class/method "'.$class.'->getRawText()" for text format "'.$textFormat.'"', DEVLOG_SEVERITY_WARNING);
                }
            }
        } else {
            Helper::devLog('Unsupported text format "'.$textFormat.'" in physical node with @ID "'.$id.'"', DEVLOG_SEVERITY_WARNING);
        }
        return $rawText;
    }

    /**
     * This determines a title for the given document
     *
     * @access public
     *
     * @static
     *
     * @param integer $uid: The UID of the document
     * @param boolean $recursive: Search superior documents for a title, too?
     *
     * @return string The title of the document itself or a parent document
     */
    public static function getTitle($uid, $recursive = FALSE) {
        $title = '';
        // Sanitize input.
        $uid = max(intval($uid), 0);
        if ($uid) {
            $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'tx_dlf_documents.title,tx_dlf_documents.partof',
                'tx_dlf_documents',
                'tx_dlf_documents.uid='.$uid
                    .Helper::whereClause('tx_dlf_documents'),
                '',
                '',
                '1'
            );
            if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {
                // Get title information.
                list ($title, $partof) = $GLOBALS['TYPO3_DB']->sql_fetch_row($result);
                // Search parent documents recursively for a title?
                if ($recursive
                    && empty($title)
                    && intval($partof)
                    && $partof != $uid) {
                    $title = self::getTitle($partof, TRUE);
                }
            } else {
                Helper::devLog('No document with UID '.$uid.' found or document not accessible', DEVLOG_SEVERITY_WARNING);
            }
        } else {
            Helper::devLog('Invalid UID '.$uid.' for document', DEVLOG_SEVERITY_ERROR);
        }
        return $title;
    }

    /**
     * This extracts all the metadata for the toplevel logical structure node / resource
     *
     * @access public
     *
     * @param integer $cPid: The PID for the metadata definitions
     *
     * @return array The logical structure node's / resource's parsed metadata array
     */
    public function getTitledata($cPid = 0) {
        $titledata = $this->getMetadata($this->_getToplevelId(), $cPid);
        // Set record identifier for METS file / IIIF manifest if not present.
        if (is_array($titledata)
            && array_key_exists('record_id', $titledata)) {
            if (!empty($this->recordId)
                && !in_array($this->recordId, $titledata['record_id'])) {
                array_unshift($titledata['record_id'], $this->recordId);
            }
        }
        return $titledata;
    }

    /**
     * Traverse a logical (sub-) structure tree to find the structure with the requested logical id and return it's depth.
     *
     * @access protected
     *
     * @param array $structure: logical structure array
     * @param integer $depth: current tree depth
     * @param string $logId: ID of the logical structure whose depth is requested
     *
     * @return integer|boolean: false if structure with $logId is not a child of this substructure,
     * or the actual depth.
     */
    protected function getTreeDepth($structure, $depth, $logId) {
        foreach ($structure as $element) {
            if ($element['id'] == $logId) {
                return $depth;
            } elseif (array_key_exists('children', $element)) {
                $foundInChildren = $this->getTreeDepth($element['children'], $depth + 1, $logId);
                if ($foundInChildren!==false) {
                    return $foundInChildren;
                }
            }
        }
        return false;
    }

    /**
     * Get the tree depth of a logical structure element within the table of content
     *
     * @access public
     *
     * @param string $logId: The id of the logical structure element whose depth is requested
     * @return number|boolean tree depth as integer or FALSE if no element with $logId exists within the TOC.
     */
    public function getStructureDepth($logId) {
        return $this->getTreeDepth($this->_getTableOfContents(), 1, $logId);
    }

    /**
     * This sets some basic class properties
     *
     * @access protected
     *
     * @abstract
     *
     * @return void
     */
    protected abstract function init();

    /**
     * Reuse any document object that might have been already loaded to determine wether document is METS or IIIF
     *
     * @access protected
     *
     * @abstract
     *
     * @param \SimpleXMLElement|IiifResourceInterface $preloadedDocument: any instance that has already been loaded
     *
     * @return boolean true if $preloadedDocument can actually be reused, false if it has to be loaded again
     */
    protected abstract function setPreloadedDocument($preloadedDocument);

    /**
     * METS/IIIF specific part of loading a location
     *
     * @access protected
     *
     * @abstract
     *
     * @param string $location: The URL of the file to load
     */
    protected abstract function loadLocation($location);

    /**
     * Load XML file / IIIF resource from URL
     *
     * @access protected
     *
     * @param string $location: The URL of the file to load
     *
     * @return boolean TRUE on success or FALSE on failure
     */
    protected function load($location) {
        // Load XML / JSON-LD file.
        if (\TYPO3\CMS\Core\Utility\GeneralUtility::isValidUrl($location)) {
            // Load extension configuration
            $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dlf']);
            // Set user-agent to identify self when fetching XML / JSON-LD data.
            if (!empty($extConf['useragent'])) {
                @ini_set('user_agent', $extConf['useragent']);
            }
            // the actual loading is format specific
            return $this->loadLocation($location);
        } else {
            Helper::devLog('Invalid file location "'.$location.'" for document loading', DEVLOG_SEVERITY_ERROR);
        }
        return FALSE;
    }

    /**
     * Analyze the document if it contains any fulltext that needs to be indexed.
     *
     * @access protected
     *
     * @abstract
     */
    protected abstract function ensureHasFulltextIsSet();

    /**
     * Register all available data formats
     *
     * @access protected
     *
     * @return void
     */
    protected function loadFormats() {
        if (!$this->formatsLoaded) {
            // Get available data formats from database.
            $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'tx_dlf_formats.type AS type,tx_dlf_formats.root AS root,tx_dlf_formats.namespace AS namespace,tx_dlf_formats.class AS class',
                'tx_dlf_formats',
                'tx_dlf_formats.pid=0'
                    .Helper::whereClause('tx_dlf_formats'),
                '',
                '',
                ''
            );
            while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
                // Update format registry.
                $this->formats[$resArray['type']] = [
                    'rootElement' => $resArray['root'],
                    'namespaceURI' => $resArray['namespace'],
                    'class' => $resArray['class']
                ];
            }
            $this->formatsLoaded = TRUE;
        }
    }

    /**
     * Register all available namespaces for a \SimpleXMLElement object
     *
     * @access public
     *
     * @param \SimpleXMLElement|\DOMXPath &$obj: \SimpleXMLElement or \DOMXPath object
     *
     * @return void
     */
    public function registerNamespaces(&$obj) {
        // TODO Check usage. XML specific method does not seem to be used anywhere outside this class within the project, but it is public and may be used by extensions.
        $this->loadFormats();
        // Do we have a \SimpleXMLElement or \DOMXPath object?
        if ($obj instanceof \SimpleXMLElement) {
            $method = 'registerXPathNamespace';
        } elseif ($obj instanceof \DOMXPath) {
            $method = 'registerNamespace';
        } else {
            Helper::devLog('Given object is neither a SimpleXMLElement nor a DOMXPath instance', DEVLOG_SEVERITY_ERROR);
            return;
        }
        // Register metadata format's namespaces.
        foreach ($this->formats as $enc => $conf) {
            $obj->$method(strtolower($enc), $conf['namespaceURI']);
        }
    }

    /**
     * This saves the document to the database and index
     *
     * @access public
     *
     * @param integer $pid: The PID of the saved record
     * @param integer $core: The UID of the Solr core for indexing
     *
     * @return boolean TRUE on success or FALSE on failure
     */
    public function save($pid = 0, $core = 0) {
        if (TYPO3_MODE !== 'BE') {
            Helper::devLog('Saving a document is only allowed in the backend', DEVLOG_SEVERITY_ERROR);
            return FALSE;
        }
        // Make sure $pid is a non-negative integer.
        $pid = max(intval($pid), 0);
        // Make sure $core is a non-negative integer.
        $core = max(intval($core), 0);
        // If $pid is not given, try to get it elsewhere.
        if (!$pid
            && $this->pid) {
            // Retain current PID.
            $pid = $this->pid;
        } elseif (!$pid) {
            Helper::devLog('Invalid PID '.$pid.' for document saving', DEVLOG_SEVERITY_ERROR);
            return FALSE;
        }
        // Set PID for metadata definitions.
        $this->cPid = $pid;
        // Set UID placeholder if not updating existing record.
        if ($pid != $this->pid) {
            $this->uid = uniqid('NEW');
        }
        // Get metadata array.
        $metadata = $this->getTitledata($pid);
        // Check for record identifier.
        if (empty($metadata['record_id'][0])) {
            Helper::devLog('No record identifier found to avoid duplication', DEVLOG_SEVERITY_ERROR);
            return FALSE;
        }
        // Load plugin configuration.
        $conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::$extKey]);
        // Get UID for structure type.
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_dlf_structures.uid AS uid',
            'tx_dlf_structures',
            'tx_dlf_structures.pid='.intval($pid)
                .' AND tx_dlf_structures.index_name='.$GLOBALS['TYPO3_DB']->fullQuoteStr($metadata['type'][0], 'tx_dlf_structures')
                .Helper::whereClause('tx_dlf_structures'),
            '',
            '',
            '1'
        );
        if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {
            list ($structure) = $GLOBALS['TYPO3_DB']->sql_fetch_row($result);
        } else {
            Helper::devLog('Could not identify document/structure type "'.$GLOBALS['TYPO3_DB']->fullQuoteStr($metadata['type'][0], 'tx_dlf_structures').'"', DEVLOG_SEVERITY_ERROR);
            return FALSE;
        }
        $metadata['type'][0] = $structure;
        // Get UIDs for collections.
        $collections = [];
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_dlf_collections.index_name AS index_name,tx_dlf_collections.uid AS uid',
            'tx_dlf_collections',
            'tx_dlf_collections.pid='.intval($pid)
                .' AND tx_dlf_collections.sys_language_uid IN (-1,0)'
                .Helper::whereClause('tx_dlf_collections'),
            '',
            '',
            ''
        );
        while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
            $collUid[$resArray['index_name']] = $resArray['uid'];
        }
        foreach ($metadata['collection'] as $collection) {
            if (!empty($collUid[$collection])) {
                // Add existing collection's UID.
                $collections[] = $collUid[$collection];
            } else {
                // Insert new collection.
                $collNewUid = uniqid('NEW');
                $collData['tx_dlf_collections'][$collNewUid] = [
                    'pid' => $pid,
                    'label' => $collection,
                    'index_name' => $collection,
                    'oai_name' => (!empty($conf['publishNewCollections']) ? Helper::getCleanString($collection) : ''),
                    'description' => '',
                    'documents' => 0,
                    'owner' => 0,
                    'status' => 0,
                ];
                $substUid = Helper::processDBasAdmin($collData);
                // Prevent double insertion.
                unset ($collData);
                // Add new collection's UID.
                $collections[] = $substUid[$collNewUid];
                if ((TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI) == FALSE) {
                    Helper::addMessage(
                        htmlspecialchars(sprintf(Helper::getMessage('flash.newCollection'), $collection, $substUid[$collNewUid])),
                        Helper::getMessage('flash.attention', TRUE),
                        \TYPO3\CMS\Core\Messaging\FlashMessage::INFO,
                        TRUE
                    );
                }
            }
        }
        $metadata['collection'] = $collections;
        // Get UID for owner.
        $owner = !empty($metadata['owner'][0]) ? $metadata['owner'][0] : 'default';
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_dlf_libraries.uid AS uid',
            'tx_dlf_libraries',
            'tx_dlf_libraries.pid='.intval($pid)
                .' AND tx_dlf_libraries.index_name='.$GLOBALS['TYPO3_DB']->fullQuoteStr($owner, 'tx_dlf_libraries')
                .Helper::whereClause('tx_dlf_libraries'),
            '',
            '',
            '1'
        );
        if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {
            list ($ownerUid) = $GLOBALS['TYPO3_DB']->sql_fetch_row($result);
        } else {
            // Insert new library.
            $libNewUid = uniqid('NEW');
            $libData['tx_dlf_libraries'][$libNewUid] = [
                'pid' => $pid,
                'label' => $owner,
                'index_name' => $owner,
                'website' => '',
                'contact' => '',
                'image' => '',
                'oai_label' => '',
                'oai_base' => '',
                'opac_label' => '',
                'opac_base' => '',
                'union_label' => '',
                'union_base' => '',
            ];
            $substUid = Helper::processDBasAdmin($libData);
            // Add new library's UID.
            $ownerUid = $substUid[$libNewUid];
            if ((TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI) == FALSE) {
                Helper::addMessage(
                    htmlspecialchars(sprintf(Helper::getMessage('flash.newLibrary'), $owner, $ownerUid)),
                    Helper::getMessage('flash.attention', TRUE),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::INFO,
                    TRUE
                );
            }
        }
        $metadata['owner'][0] = $ownerUid;
        // Get UID of parent document.
        $partof = $this->getParentDocumentUidForSaving($pid, $core);
        // Use the date of publication or title as alternative sorting metric for parts of multi-part works.
        if (!empty($partof)) {
            if (empty($metadata['volume'][0])
                && !empty($metadata['year'][0])) {
                $metadata['volume'] = $metadata['year'];
            }
            if (empty($metadata['volume_sorting'][0])) {
                if (!empty($metadata['year_sorting'][0])) {
                    $metadata['volume_sorting'][0] = $metadata['year_sorting'][0];
                } elseif (!empty($metadata['year'][0])) {
                    $metadata['volume_sorting'][0] = $metadata['year'][0];
                }
            }
            // If volume_sorting is still empty, try to use title_sorting finally (workaround for newspapers)
            if (empty($metadata['volume_sorting'][0])) {
                if (!empty($metadata['title_sorting'][0])) {
                    $metadata['volume_sorting'][0] = $metadata['title_sorting'][0];
                }
            }
        }
        // Get metadata for lists and sorting.
        $listed = [];
        $sortable = [];
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_dlf_metadata.index_name AS index_name,tx_dlf_metadata.is_listed AS is_listed,tx_dlf_metadata.is_sortable AS is_sortable',
            'tx_dlf_metadata',
            '(tx_dlf_metadata.is_listed=1 OR tx_dlf_metadata.is_sortable=1)'
                .' AND tx_dlf_metadata.pid='.intval($pid)
                .Helper::whereClause('tx_dlf_metadata'),
            '',
            '',
            ''
        );
        while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
            if (!empty($metadata[$resArray['index_name']])) {
                if ($resArray['is_listed']) {
                    $listed[$resArray['index_name']] = $metadata[$resArray['index_name']];
                }
                if ($resArray['is_sortable']) {
                    $sortable[$resArray['index_name']] = $metadata[$resArray['index_name']][0];
                }
            }
        }
        // Fill data array.
        $data['tx_dlf_documents'][$this->uid] = [
            'pid' => $pid,
            $GLOBALS['TCA']['tx_dlf_documents']['ctrl']['enablecolumns']['starttime'] => 0,
            $GLOBALS['TCA']['tx_dlf_documents']['ctrl']['enablecolumns']['endtime'] => 0,
            'prod_id' => $metadata['prod_id'][0],
            'location' => $this->location,
            'record_id' => $metadata['record_id'][0],
            'opac_id' => $metadata['opac_id'][0],
            'union_id' => $metadata['union_id'][0],
            'urn' => $metadata['urn'][0],
            'purl' => $metadata['purl'][0],
            'title' => $metadata['title'][0],
            'title_sorting' => $metadata['title_sorting'][0],
            'author' => implode('; ', $metadata['author']),
            'year' => implode('; ', $metadata['year']),
            'place' => implode('; ', $metadata['place']),
            'thumbnail' => $this->_getThumbnail(TRUE),
            'metadata' => serialize($listed),
            'metadata_sorting' => serialize($sortable),
            'structure' => $metadata['type'][0],
            'partof' => $partof,
            'volume' => $metadata['volume'][0],
            'volume_sorting' => $metadata['volume_sorting'][0],
            'collections' => $metadata['collection'],
            'owner' => $metadata['owner'][0],
            'solrcore' => $core,
            'status' => 0,
            'document_format' => $metadata['document_format'][0],
        ];
        // Unhide hidden documents.
        if (!empty($conf['unhideOnIndex'])) {
            $data['tx_dlf_documents'][$this->uid][$GLOBALS['TCA']['tx_dlf_documents']['ctrl']['enablecolumns']['disabled']] = 0;
        }
        // Process data.
        $newIds = Helper::processDBasAdmin($data);
        // Replace placeholder with actual UID.
        if (strpos($this->uid, 'NEW') === 0) {
            $this->uid = $newIds[$this->uid];
            $this->pid = $pid;
            $this->parentId = $partof;
        }
        if ((TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI) == FALSE) {
            Helper::addMessage(
                htmlspecialchars(sprintf(Helper::getMessage('flash.documentSaved'), $metadata['title'][0], $this->uid)),
                Helper::getMessage('flash.done', TRUE),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK,
                TRUE
            );
        }
        // Add document to index.
        if ($core) {
            Indexer::add($this, $core);
        } else {
            Helper::devLog('Invalid UID "'.$core.'" for Solr core', DEVLOG_SEVERITY_NOTICE);
        }
        return TRUE;
    }

    /**
     * Get the ID of the parent document if the current document has one. Also save a parent document
     * to the database and the Solr index if their $pid and the current $pid differ.
     * Currently only applies to METS documents.
     *
     * @access protected
     *
     * @abstract
     *
     * @return int The parent document's id.
     */
    protected abstract function getParentDocumentUidForSaving($pid, $core);

    /**
     * This returns $this->hasFulltext via __get()
     *
     * @access protected
     *
     * @return boolean Are there any fulltext files available?
     */
    protected function _getHasFulltext() {
        $this->ensureHasFulltextIsSet();
        return $this->hasFulltext;
    }

    /**
     * This returns $this->location via __get()
     *
     * @access protected
     *
     * @return string The location of the document
     */
    protected function _getLocation() {
        return $this->location;
    }

    /**
     * Format specific part of building the document's metadata array
     *
     * @access protected
     *
     * @abstract
     *
     * @param integer $cPid
     */
    protected abstract function prepareMetadataArray($cPid);

    /**
     * This builds an array of the document's metadata
     *
     * @access protected
     *
     * @return array Array of metadata with their corresponding logical structure node ID as key
     */
    protected function _getMetadataArray() {
        // Set metadata definitions' PID.
        $cPid = ($this->cPid ? $this->cPid : $this->pid);
        if (!$cPid) {
            Helper::devLog('Invalid PID '.$cPid.' for metadata definitions', DEVLOG_SEVERITY_ERROR);
            return [];
        }
        if (!$this->metadataArrayLoaded
            || $this->metadataArray[0] != $cPid) {
            $this->prepareMetadataArray($cPid);
            $this->metadataArray[0] = $cPid;
            $this->metadataArrayLoaded = TRUE;
        }
        return $this->metadataArray;
    }

    /**
     * This returns $this->numPages via __get()
     *
     * @access protected
     *
     * @return integer The total number of pages and/or tracks
     */
    protected function _getNumPages() {
        $this->_getPhysicalStructure();
        return $this->numPages;
    }

    /**
     * This returns $this->parentId via __get()
     *
     * @access protected
     *
     * @return integer The UID of the parent document or zero if not applicable
     */
    protected function _getParentId() {
        return $this->parentId;
    }

    /**
     * This builds an array of the document's physical structure
     *
     * @access protected
     *
     * @abstract
     *
     * @return array Array of physical elements' id, type, label and file representations ordered
     * by @ORDER attribute / IIIF Sequence's Canvases
     */
    protected abstract function _getPhysicalStructure();

    /**
     * This gives an array of the document's physical structure metadata
     *
     * @access protected
     *
     * @return array Array of elements' type, label and file representations ordered by @ID attribute / Canvas order
     */
    protected function _getPhysicalStructureInfo() {
        // Is there no physical structure array yet?
        if (!$this->physicalStructureLoaded) {
            // Build physical structure array.
            $this->_getPhysicalStructure();
        }
        return $this->physicalStructureInfo;
    }

    /**
     * This returns $this->pid via __get()
     *
     * @access protected
     *
     * @return integer The PID of the document or zero if not in database
     */
    protected function _getPid() {
        return $this->pid;
    }

    /**
     * This returns $this->ready via __get()
     *
     * @access protected
     *
     * @return boolean Is the document instantiated successfully?
     */
    protected function _getReady() {
        return $this->ready;
    }

    /**
     * This returns $this->recordId via __get()
     *
     * @access protected
     *
     * @return mixed The METS file's / IIIF manifest's record identifier
     */
    protected function _getRecordId() {
        return $this->recordId;
    }

    /**
     * This returns $this->rootId via __get()
     *
     * @access protected
     *
     * @return integer The UID of the root document or zero if not applicable
     */
    protected function _getRootId() {
        if (!$this->rootIdLoaded) {
            if ($this->parentId) {
                $parent = self::getInstance($this->parentId, $this->pid);
                $this->rootId = $parent->rootId;
            }
            $this->rootIdLoaded = TRUE;
        }
        return $this->rootId;
    }

    /**
     * This returns the smLinks between logical and physical structMap (METS) and models the
     * relation between IIIF Canvases and Manifests / Ranges in the same way
     *
     * @access protected
     *
     * @abstract
     *
     * @return array The links between logical and physical nodes / Range, Manifest and Canvas
     */
    protected abstract function _getSmLinks();

    /**
     * This builds an array of the document's logical structure
     *
     * @access protected
     *
     * @return array Array of structure nodes' id, label, type and physical page indexes/mptr / Canvas link with original hierarchy preserved
     */
    protected function _getTableOfContents() {
        // Is there no logical structure array yet?
        if (!$this->tableOfContentsLoaded) {
            // Get all logical structures.
            $this->getLogicalStructure('', TRUE);
            $this->tableOfContentsLoaded = TRUE;
        }
        return $this->tableOfContents;
    }

    /**
     * This returns the document's thumbnail location
     *
     * @access protected
     *
     * @abstract
     *
     * @param boolean $forceReload: Force reloading the thumbnail instead of returning the cached value
     *
     * @return string The document's thumbnail location
     */
    protected abstract function _getThumbnail($forceReload = FALSE);

    /**
     * This returns the ID of the toplevel logical structure node
     *
     * @access protected
     *
     * @abstract
     *
     * @return string The logical structure node's ID
     */
    protected abstract function _getToplevelId();

    /**
     * This returns $this->uid via __get()
     *
     * @access protected
     *
     * @return mixed The UID or the URL of the document
     */
    protected function _getUid() {
        return $this->uid;
    }

    /**
     * This sets $this->cPid via __set()
     *
     * @access protected
     *
     * @param integer $value: The new PID for the metadata definitions
     *
     * @return void
     */
    protected function _setCPid($value) {
        $this->cPid = max(intval($value), 0);
    }

    /**
     * This magic method is invoked each time a clone is called on the object variable
     * (This method is defined as private/protected because singleton objects should not be cloned)
     *
     * @access protected
     *
     * @return void
     */
    protected function __clone() {}

    /**
     * This is a singleton class, thus the constructor should be private/protected
     * (Get an instance of this class by calling \Kitodo\Dlf\Common\Document::getInstance())
     *
     * @access protected
     *
     * @param integer $uid: The UID of the document to parse or URL to XML file
     * @param integer $pid: If > 0, then only document with this PID gets loaded
     * @param \SimpleXMLElement|IiifResourceInterface $preloadedDocument: Either null or the \SimpleXMLElement
     * or IiifResourceInterface that has been loaded to determine the basic document format.
     *
     * @return void
     */
    protected function __construct($uid, $pid, $preloadedDocument) {
        // Prepare to check database for the requested document.
        if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid)) {
            $whereClause = 'tx_dlf_documents.uid='.intval($uid).Helper::whereClause('tx_dlf_documents');
        } else {
            // Try to load METS file / IIIF manifest.
            if ($this->setPreloadedDocument($preloadedDocument)
                || (\TYPO3\CMS\Core\Utility\GeneralUtility::isValidUrl($uid)
                && $this->load($uid))) {
                // Initialize core METS object.
                $this->init();
                if ($this->getDocument() !== NULL) {
                    // Cast to string for safety reasons.
                    $location = (string) $uid;
                    $this->establishRecordId($pid);
                } else {
                    // No METS / IIIF part found.
                    return;
                }
            } else {
                // Loading failed.
                return;
            }
            if (!empty($location)
                && !empty($this->recordId)) {
                // Try to match record identifier or location (both should be unique).
                $whereClause = '(tx_dlf_documents.location='.$GLOBALS['TYPO3_DB']->fullQuoteStr($location, 'tx_dlf_documents').' OR tx_dlf_documents.record_id='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->recordId, 'tx_dlf_documents').')'.Helper::whereClause('tx_dlf_documents');
            } else {
                // Can't persistently identify document, don't try to match at all.
                $whereClause = '1=-1';
            }
        }
        // Check for PID if needed.
        if ($pid) {
            $whereClause .= ' AND tx_dlf_documents.pid='.intval($pid);
        }
        // Get document PID and location from database.
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_dlf_documents.uid AS uid,tx_dlf_documents.pid AS pid,tx_dlf_documents.record_id AS record_id,tx_dlf_documents.partof AS partof,tx_dlf_documents.thumbnail AS thumbnail,tx_dlf_documents.location AS location',
            'tx_dlf_documents',
            $whereClause,
            '',
            '',
            '1'
        );
        if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0) {
            list ($this->uid, $this->pid, $this->recordId, $this->parentId, $this->thumbnail, $this->location) = $GLOBALS['TYPO3_DB']->sql_fetch_row($result);
            $this->thumbnailLoaded = TRUE;
            // Load XML file if necessary...
            if ($this->getDocument() === NULL
                && $this->load($this->location)) {
                // ...and set some basic properties.
                $this->init();
            }
            // Do we have a METS / IIIF object now?
            if ($this->getDocument() !== NULL) {
                // Set new location if necessary.
                if (!empty($location)) {
                    $this->location = $location;
                }
                // Document ready!
                $this->ready = TRUE;
            }
        } elseif ($this->getDocument() !== NULL) {
            // Set location as UID for documents not in database.
            $this->uid = $location;
            $this->location = $location;
            // Document ready!
            $this->ready = TRUE;
        } else {
            Helper::devLog('No document with UID '.$uid.' found or document not accessible', DEVLOG_SEVERITY_ERROR);
        }
    }

    /**
     * This magic method is called each time an invisible property is referenced from the object
     *
     * @access public
     *
     * @param string $var: Name of variable to get
     *
     * @return mixed Value of $this->$var
     */
    public function __get($var) {
        $method = '_get'.ucfirst($var);
        if (!property_exists($this, $var)
            || !method_exists($this, $method)) {
            Helper::devLog('There is no getter function for property "'.$var.'"', DEVLOG_SEVERITY_WARNING);
            return;
        } else {
            return $this->$method();
        }
    }

    /**
     * This magic method is called each time an invisible property is referenced from the object
     *
     * @access public
     *
     * @param string $var: Name of variable to set
     * @param mixed $value: New value of variable
     *
     * @return void
     */
    public function __set($var, $value) {
        $method = '_set'.ucfirst($var);
        if (!property_exists($this, $var)
            || !method_exists($this, $method)) {
            Helper::devLog('There is no setter function for property "'.$var.'"', DEVLOG_SEVERITY_WARNING);
        } else {
            $this->$method($value);
        }
    }
}
