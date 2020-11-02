<?php

declare(strict_types=1);

namespace IPS {

    class ModuleLoader
    {
        private static $libraries = [];
        private static $modules = [];

        public static function libraryExists(string $LibraryID): bool
        {
            return isset(self::$libraries[$LibraryID]);
        }

        public static function getLibrary(string $LibraryID): array
        {
            self::checkLibrary($LibraryID);

            return self::$libraries[$LibraryID];
        }

        public static function getLibraryList(): array
        {
            return array_keys(self::$libraries);
        }

        public static function getLibraryModules(string $LibraryID): array
        {
            $result = [];
            foreach (self::$modules as $module) {
                if ($module['LibraryID'] == $LibraryID) {
                    $result[] = $module['ModuleID'];
                }
            }

            return $result;
        }

        public static function moduleExists(string $ModuleID): bool
        {
            return isset(self::$modules[$ModuleID]);
        }

        public static function getModule(string $ModuleID): array
        {
            self::checkModule($ModuleID);

            return self::$modules[$ModuleID];
        }

        public static function getModuleList(): array
        {
            return array_keys(self::$modules);
        }

        public static function getModuleListByType(int $ModuleType): array
        {
            $result = [];
            foreach (self::$modules as $module) {
                if ($module['ModuleType'] == $ModuleType) {
                    $result[] = $module['ModuleID'];
                }
            }

            return $result;
        }

        public static function loadLibrary(string $file): void
        {
            $library = json_decode(file_get_contents($file), true);
            self::$libraries[$library['id']] = [
                'LibraryID' => $library['id'],
                'Author'    => $library['author'],
                'URL'       => $library['url'],
                'Name'      => $library['name'],
                'Version'   => $library['version'],
                'Build'     => $library['build'],
                'Date'      => $library['date'],
            ];
            self::loadModules(dirname($file), $library['id']);
        }

        public static function reset()
        {
            self::$libraries = [];
            self::$modules = [];
        }

        private static function checkLibrary(string $LibraryID): void
        {
            if (!self::libraryExists($LibraryID)) {
                throw new \Exception(sprintf('Library #%s does not exist', $LibraryID));
            }
        }

        private static function checkModule(string $ModuleID): void
        {
            if (!self::moduleExists($ModuleID)) {
                throw new \Exception(sprintf('Module #%s does not exist', $ModuleID));
            }
        }

        private static function loadModules(string $folder, string $libraryID): void
        {
            $modules = glob($folder . '/*', GLOB_ONLYDIR);
            $filter = ['libs', 'docs', 'imgs', 'tests'];
            foreach ($modules as $module) {
                if (!in_array(basename($module), $filter)) {
                    self::loadModule($module, $libraryID);
                }
            }
        }

        private static function loadModule(string $folder, string $libraryID): void
        {
            $module = json_decode(file_get_contents($folder . '/module.json'), true);
            self::$modules[$module['id']] = [
                'ModuleID'           => $module['id'],
                'ModuleName'         => $module['name'],
                'ModuleType'         => $module['type'],
                'Vendor'             => $module['vendor'],
                'Aliases'            => $module['aliases'],
                'ParentRequirements' => $module['parentRequirements'],
                'ChildRequirements'  => $module['childRequirements'],
                'Implemented'        => $module['implemented'],
                'LibraryID'          => $libraryID,
                'Prefix'             => $module['prefix'],
                'Class'              => str_replace(' ', '', $module['name'])
            ];

            //Include module class file
            require_once $folder . '/module.php';

            self::registerFunctions($module['name'], $module['prefix']);
        }

        private static function registerFunctions($moduleName, $modulePrefix)
        {
            $class = new \ReflectionClass(str_replace(' ', '', $moduleName));
            foreach ($class->GetMethods() as $method) {
                if (!$method->isPublic()) {
                    continue;
                }
                if (in_array($method->GetName(), ['__construct', '__destruct', '__call', '__callStatic', '__get', '__set', '__isset', '__sleep', '__wakeup', '__toString', '__invoke', '__set_state', '__clone', '__debuginfo', 'Create', 'Destroy', 'ApplyChanges', 'ReceiveData', 'ForwardData', 'RequestAction', 'MessageSink', 'GetConfigurationForm', 'GetConfigurationForParent', 'Translate'])) {
                    continue;
                }
                $params = ['int $InstanceID'];
                $fwdparams = [];
                foreach ($method->getParameters() as $parameter) {
                    $type = @$parameter->GetClass();
                    if ($type !== false && $type !== null) {
                        $type = strtolower($type->GetName());
                    } else {
                        $type = $parameter->GetType();
                    }
                    $params[] = $type . ' $' . $parameter->GetName();
                    $fwdparams[] = '$' . $parameter->GetName();
                }
                $function = sprintf('function %s_%s(%s){return IPS\InstanceManager::getInstanceInterface($InstanceID)->%s(%s);}', $modulePrefix, $method->GetName(), implode(', ', $params), $method->GetName(), implode(', ', $fwdparams));
                if (!\function_exists($modulePrefix . '_' . $method->GetName())) {
                    eval($function);
                }
            }
        }
    }

    class ObjectManager
    {
        private static $availableIDs = [];

        private static $objects = [];

        public static function registerObject(int $ObjectType): int
        {
            if (count(self::$objects) == 0) {
                throw new \Exception('Reset was not called on Kernel.');
            }

            //Initialize
            if (count(self::$availableIDs) == 0 && count(self::$objects) == 1) {
                for ($i = 10000; $i < 60000; $i++) {
                    self::$availableIDs[] = $i;
                }
                shuffle(self::$availableIDs);
            }

            //Check for availability
            if (count(self::$availableIDs) == 0) {
                throw new \Exception('No usable IDs left. Please contact support.');
            }

            //Fetch first. The array is already randomized
            $id = array_shift(self::$availableIDs);

            //Add object
            self::$objects[$id] = [
                'ObjectID'         => $id,
                'ObjectType'       => $ObjectType,
                'ObjectName'       => sprintf('Unnamed Object (ID: %d)', $id),
                'ObjectIcon'       => '',
                'ObjectInfo'       => '',
                'ObjectIdent'      => '',
                'ObjectSummary'    => '',
                'ObjectIsHidden'   => false,
                'ObjectIsDisabled' => false,
                'ObjectIsLocked'   => false,
                'ObjectIsReadOnly' => false,
                'ObjectPosition'   => 0,
                'ParentID'         => 0,
                'ChildrenIDs'      => [],
                'HasChildren'      => false
            ];

            //Add to root
            self::$objects[0]['ChildrendIDs'][] = $id;
            self::$objects[0]['HasChildren'] = true;

            return $id;
        }

        public static function unregisterObject(int $ID): void
        {
            self::checkObject($ID);

            if (self::hasChildren($ID)) {
                throw new \Exception('Cannot call UnregisterObject if a children is present');
            }

            //Delete ID from Children array
            $ParentID = self::$objects[$ID]['ParentID'];
            if (($key = array_search($ID, self::$objects[$ParentID]['ChildrenIDs'])) !== false) {
                unset(self::$objects[$ParentID]['ChildrenIDs'][$key]);
            }

            //Readd ID to available pool
            self::$availableIDs[] = $ID;
        }

        public static function setParent(int $ID, int $ParentID): void
        {
            self::checkRoot($ID);
            self::checkObject($ID);

            self::$objects[self::$objects[$ID]['ParentID']]['ChildrenIDs'] = array_diff(self::$objects[self::$objects[$ID]['ParentID']]['ChildrenIDs'], [$ID]);
            self::$objects[$ID]['ParentID'] = $ParentID;
            self::$objects[$ParentID]['ChildrenIDs'][] = $ID;
        }

        public static function setIdent(int $ID, string $Ident): void
        {
            self::checkObject($ID);

            if (!preg_match('/[a-zA-Z0-9_]*/', $Ident)) {
                throw new \Exception('Ident may contain only letters and numbers');
            }

            if ($Ident != '') {
                $ParentID = self::$objects[$ID]['ParentID'];
                foreach (self::$objects[$ParentID]['ChildrenIDs'] as $ChildID) {
                    if (self::$objects[$ChildID]['ObjectIdent'] == $Ident) {
                        if ($ChildID != $ID) {
                            throw new \Exception('Ident must be unique for each category');
                        }
                    }
                }
            }

            self::$objects[$ID]['ObjectIdent'] = $Ident;
        }

        public static function setName(int $ID, string $Name): void
        {
            self::checkObject($ID);

            if ($Name == '') {
                $Name = sprintf('Unnamed Object (ID: %d)', $ID);
            }

            self::$objects[$ID]['ObjectName'] = $Name;
        }

        public static function setInfo(int $ID, string $Info): void
        {
            self::checkObject($ID);

            self::$objects[$ID]['ObjectInfo'] = $Info;
        }

        public static function setIcon(int $ID, string $Icon): void
        {
            self::checkObject($ID);

            self::$objects[$ID]['ObjectIcon'] = $Icon;
        }

        public static function setSummary(int $ID, string $Summary): void
        {
            self::checkRoot($ID);

            self::$objects[$ID]['ObjectSummary'] = $Summary;
        }

        public static function setPosition(int $ID, int $Position): void
        {
            self::checkRoot($ID);
            self::checkObject($ID);

            self::$objects[$ID]['ObjectPosition'] = $Position;
        }

        public static function setReadOnly(int $ID, bool $ReadOnly): void
        {
            self::checkRoot($ID);
            self::checkObject($ID);

            self::$objects[$ID]['ObjectIsReadOnly'] = $ReadOnly;
        }

        public static function setHidden(int $ID, bool $Hidden): void
        {
            self::checkRoot($ID);
            self::checkObject($ID);

            self::$objects[$ID]['ObjectIsHidden'] = $Hidden;
        }

        public static function setDisabled(int $ID, bool $Disabled): void
        {
            self::checkRoot($ID);
            self::checkObject($ID);

            self::$objects[$ID]['ObjectIsDisabled'] = $Disabled;
        }

        public static function objectExists(int $ID): bool
        {
            return isset(self::$objects[$ID]);
        }

        public static function getObject(int $ID): array
        {
            self::checkObject($ID);

            return self::$objects[$ID];
        }

        public static function getObjectList(): array
        {
            return array_keys(self::$objects);
        }

        public static function getObjectIDByName(string $Name, int $ParentID): array
        {
            if ($Name == '') {
                throw new \Exception('Name cannot be empty');
            }

            self::checkObject($ParentID);
            foreach (self::$objects[$ParentID]['ChildrenIDs'] as $ChildID) {
                self::checkObject($ChildID);
                if (self::$objects[$ChildID]['ObjectName'] == $Name) {
                    return $ChildID;
                }
            }

            throw new \Exception(sprintf('Object with name %s could not be found', $Name));
        }

        public static function getObjectIDByNameEx(string $Name, int $ParentID, int $ObjectType): int
        {
            if ($Name == '') {
                throw new \Exception('Name cannot be empty');
            }

            self::checkObject($ParentID);
            foreach (self::$objects[$ParentID]['ChildrenIDs'] as $ChildID) {
                self::checkObject($ChildID);
                if (self::$objects[$ChildID]['ObjectType'] == $ObjectType) {
                    if (self::$objects[$ChildID]['ObjectName'] == $Name) {
                        return $ChildID;
                    }
                }
            }

            throw new \Exception(sprintf('Object with name %s could not be found', $Name));
        }

        public static function getObjectIDByIdent(string $Ident, int $ParentID)
        {
            if ($Ident == '') {
                throw new \Exception('Ident cannot be empty');
            }

            self::checkObject($ParentID);
            foreach (self::$objects[$ParentID]['ChildrenIDs'] as $ChildID) {
                self::checkObject($ChildID);
                if (self::$objects[$ChildID]['ObjectIdent'] == $Ident) {
                    return $ChildID;
                }
            }

            trigger_error(sprintf('Object with ident %s could not be found', $Ident));
            return false;
        }

        public static function hasChildren(int $ID): bool
        {
            return count(self::getChildrenIDs($ID)) > 0;
        }

        public static function isChild(int $ID, int $ParentID, bool $Recursive): bool
        {
            throw new \Exception('FIXME: Not implemented');
        }

        public static function getChildrenIDs(int $ID): array
        {
            self::checkObject($ID);

            return self::$objects[$ID]['ChildrenIDs'];
        }

        public static function getName(int $ID): string
        {
            return self::$objects[$ID]['ObjectName'];
        }

        public static function getParent(int $ID): int
        {
            return self::$objects[$ID]['ParentID'];
        }

        public static function getLocation(int $ID): string
        {
            $result = self::getName($ID);
            $parentID = self::getParent($ID);

            while ($parentID > 0) {
                $result = self::getName($parentID) . '\\' . $result;
                $parentID = self::getParent($parentID);
            }

            return $result;
        }

        public static function reset()
        {
            self::$availableIDs = [];
            self::$objects = [
                0 => [
                    'ObjectID'         => 0,
                    'ObjectType'       => 0 /* Category */,
                    'ObjectName'       => 'IP-Symcon',
                    'ObjectIcon'       => '',
                    'ObjectInfo'       => '',
                    'ObjectIdent'      => '',
                    'ObjectSummary'    => '',
                    'ObjectIsHidden'   => false,
                    'ObjectIsDisabled' => false,
                    'ObjectIsLocked'   => false,
                    'ObjectIsReadOnly' => false,
                    'ObjectPosition'   => 0,
                    'ParentID'         => 0,
                    'ChildrenIDs'      => [],
                    'HasChildren'      => false
                ]
            ];
        }

        private static function checkRoot(int $ID): void
        {
            if ($ID == 0) {
                throw new \Exception('Cannot change root');
            }
        }

        private static function checkObject(int $ID): void
        {
            if (!self::objectExists($ID)) {
                throw new \Exception(sprintf('Object #%d does not exist', $ID));
            }
        }
    }

    class CategoryManager
    {
        private static $categories = [];

        public static function createCategory(int $CategoryID): void
        {
            self::$categories[$CategoryID] = [];
        }

        public static function deleteCategory(int $CategoryID): void
        {
            self::checkCategory($CategoryID);
            unset(self::$categories[$CategoryID]);
        }

        public static function categoryExists(int $CategoryID): bool
        {
            return isset(self::$categories[$CategoryID]);
        }

        public static function getCategory(int $CategoryID): array
        {
            self::checkCategory($CategoryID);

            return [];
        }

        public static function getCategoryList(): array
        {
            return array_keys(self::$categories);
        }

        public static function reset()
        {
            self::$categories = [];
        }

        private static function checkCategory(int $CategoryID): void
        {
            if (!self::categoryExists($CategoryID)) {
                throw new \Exception(sprintf('Category #%d does not exist', $CategoryID));
            }
        }
    }

    class InstanceManager
    {
        private static $instances = [];
        private static $interfaces = [];

        public static function createInstance(int $InstanceID, array $Module): void
        {
            if (!class_exists($Module['Class'])) {
                throw new \Exception(sprintf('Cannot find class %s', $Module['Class']));
            }

            if (!in_array('IPSModule', class_parents($Module['Class']))) {
                throw new \Exception(sprintf('Class %s does not inherit from IPSModule', $Module['Class']));
            }

            self::$instances[$InstanceID] = [
                'InstanceID'      => $InstanceID,
                'ConnectionID'    => 0,
                'InstanceStatus'  => 100 /* IS_CREATING */,
                'InstanceChanged' => time(),
                'ModuleInfo'      => [
                    'ModuleID'   => $Module['ModuleID'],
                    'ModuleName' => $Module['ModuleName'],
                    'ModuleType' => $Module['ModuleType']
                ],
            ];

            $interface = new $Module['Class']($InstanceID);

            self::$interfaces[$InstanceID] = $interface;

            if ($interface instanceof \IPSModule) {
                $interface->Create();
                $interface->ApplyChanges();
            }
        }

        public static function deleteInstance(int $InstanceID): void
        {
            self::checkInstance($InstanceID);
            unset(self::$instances[$InstanceID]);
            unset(self::$interfaces[$InstanceID]);
        }

        public static function instanceExists(int $InstanceID): bool
        {
            return isset(self::$instances[$InstanceID]);
        }

        public static function getInstance(int $InstanceID): array
        {
            self::checkInstance($InstanceID);

            return self::$instances[$InstanceID];
        }

        public static function getInstanceInterface(int $InstanceID): \IPSModule
        {
            self::checkInstance($InstanceID);

            return self::$interfaces[$InstanceID];
        }

        public static function getInstanceList(): array
        {
            return array_keys(self::$instances);
        }

        public static function getInstanceListByModuleType(int $ModuleType): array
        {
            $result = [];
            foreach (self::$instances as $instance) {
                if ($instance['ModuleInfo']['ModuleType'] == $ModuleType) {
                    $result[] = $instance['InstanceID'];
                }
            }

            return $result;
        }

        public static function getInstanceListByModuleID(string $ModuleID): array
        {
            $result = [];
            foreach (self::$instances as $instance) {
                if ($instance['ModuleInfo']['ModuleID'] == $ModuleID) {
                    $result[] = $instance['InstanceID'];
                }
            }

            return $result;
        }

        public static function setStatus($InstanceID, $Status): void
        {
            self::checkInstance($InstanceID);

            self::$instances[$InstanceID]['InstanceStatus'] = $Status;
        }

        public static function getStatus($InstanceID): int
        {
            self::checkInstance($InstanceID);

            return self::$instances[$InstanceID]['InstanceStatus'];
        }

        public static function connectInstance(int $InstanceID, int $ParentID): void
        {
            self::checkInstance($InstanceID);
            self::$instances[$InstanceID]['ConnectionID'] = $ParentID;
        }

        public static function disconnectInstance(int $InstanceID): void
        {
            self::checkInstance($InstanceID);
            self::$instances[$InstanceID]['ConnectionID'] = 0;
        }

        public static function getReferenceList($InstanceID)
        {
            self::checkInstance($InstanceID);

            return self::$interfaces[$InstanceID]->GetReferenceList();
        }

        public static function reset()
        {
            self::$instances = [];
            self::$interfaces = [];
        }

        private static function checkInstance(int $InstanceID): void
        {
            if (!self::instanceExists($InstanceID)) {
                throw new \Exception(sprintf('Instance #%d does not exist', $InstanceID));
            }
        }
    }

    class VariableManager
    {
        private static $variables = [];

        public static function createVariable(int $VariableID, int $VariableType): void
        {
            switch ($VariableType) {
                case 0: /* Boolean */
                    $VariableValue = false;
                    break;
                case 1: /* Integer */
                    $VariableValue = 0;
                    break;
                case 2: /* Float */
                    $VariableValue = 0.0;
                    break;
                case 3: /* String */
                    $VariableValue = '';
                    break;
                default:
                    throw new \Exception('Unsupported VariableType!');
            }

            self::$variables[$VariableID] = [
                'VariableID'            => $VariableID,
                'VariableProfile'       => '',
                'VariableAction'        => 0,
                'VariableCustomProfile' => '',
                'VariableCustomAction'  => 0,
                'VariableUpdated'       => 0,
                'VariableChanged'       => 0,
                'VariableType'          => $VariableType,
                'VariableValue'         => $VariableValue,
                'VariableIsLocked'      => false
            ];
        }

        public static function deleteVariable(int $VariableID): void
        {
            self::checkVariable($VariableID);
            unset(self::$variables[$VariableID]);
        }

        public static function readVariableBoolean(int $VariableID): bool
        {
            self::checkVariable($VariableID);

            return self::$variables[$VariableID]['VariableValue'];
        }

        public static function writeVariableBoolean(int $VariableID, bool $VariableValue): void
        {
            self::checkVariable($VariableID);

            self::$variables[$VariableID]['VariableUpdated'] = time();
            if (self::$variables[$VariableID]['VariableValue'] != $VariableValue) {
                self::$variables[$VariableID]['VariableChanged'] = time();
            }
            self::$variables[$VariableID]['VariableValue'] = $VariableValue;
        }

        public static function readVariableInteger(int $VariableID): int
        {
            self::checkVariable($VariableID);

            return self::$variables[$VariableID]['VariableValue'];
        }

        public static function writeVariableInteger(int $VariableID, int $VariableValue): void
        {
            self::checkVariable($VariableID);

            self::$variables[$VariableID]['VariableUpdated'] = time();
            if (self::$variables[$VariableID]['VariableValue'] != $VariableValue) {
                self::$variables[$VariableID]['VariableChanged'] = time();
            }
            self::$variables[$VariableID]['VariableValue'] = $VariableValue;
        }

        public static function readVariableFloat(int $VariableID): float
        {
            self::checkVariable($VariableID);

            return self::$variables[$VariableID]['VariableValue'];
        }

        public static function writeVariableFloat(int $VariableID, float $VariableValue): void
        {
            self::checkVariable($VariableID);

            self::$variables[$VariableID]['VariableUpdated'] = time();
            if (self::$variables[$VariableID]['VariableValue'] != $VariableValue) {
                self::$variables[$VariableID]['VariableChanged'] = time();
            }
            self::$variables[$VariableID]['VariableValue'] = $VariableValue;
        }

        public static function readVariableString(int $VariableID): string
        {
            self::checkVariable($VariableID);

            return self::$variables[$VariableID]['VariableValue'];
        }

        public static function writeVariableString(int $VariableID, string $VariableValue): void
        {
            self::checkVariable($VariableID);

            self::$variables[$VariableID]['VariableUpdated'] = time();
            if (self::$variables[$VariableID]['VariableValue'] != $VariableValue) {
                self::$variables[$VariableID]['VariableChanged'] = time();
            }
            self::$variables[$VariableID]['VariableValue'] = $VariableValue;
        }

        public static function variableExists(int $VariableID): bool
        {
            return isset(self::$variables[$VariableID]);
        }

        public static function checkVariable(int $VariableID): void
        {
            if (!self::variableExists($VariableID)) {
                throw new \Exception(sprintf('Variable #%d does not exist', $VariableID));
            }
        }

        public static function getVariable(int $VariableID): array
        {
            self::checkVariable($VariableID);

            return self::$variables[$VariableID];
        }

        public static function getVariableList(): array
        {
            return array_keys(self::$variables);
        }

        public static function setVariableCustomProfile(int $VariableID, string $ProfileName): void
        {
            self::checkVariable($VariableID);

            self::$variables[$VariableID]['VariableCustomProfile'] = $ProfileName;
        }

        public static function setVariableCustomAction(int $VariableID, int $ScriptID): void
        {
            self::checkVariable($VariableID);

            self::$variables[$VariableID]['VariableCustomAction'] = $ScriptID;
        }

        public static function setVariableProfile(int $VariableID, string $ProfileName): void
        {
            self::checkVariable($VariableID);

            self::$variables[$VariableID]['VariableProfile'] = $ProfileName;
        }

        public static function setVariableAction(int $VariableID, int $InstanceID): void
        {
            self::checkVariable($VariableID);

            self::$variables[$VariableID]['VariableAction'] = $InstanceID;
        }

        public static function reset()
        {
            self::$variables = [];
        }
    }

    class ScriptManager
    {
        private static $scripts = [];
        private static $content = [];

        public static function createScript(int $ScriptID, int $ScriptType): void
        {
            self::$scripts[$ScriptID] = [
                'ScriptID'       => $ScriptID,
                'ScriptType'     => $ScriptType,
                'ScriptFile'     => $ScriptID . '.ips.php',
                'ScriptExecuted' => 0,
                'ScriptIsBroken' => false
            ];

            self::$content[$ScriptID] = '<?php ' . PHP_EOL . PHP_EOL . '//Start writing your scripts between the brackets' . PHP_EOL . PHP_EOL . '?>';
        }

        public static function deleteScript(int $ScriptID, bool $DeleteFile): void
        {
            self::checkScript($ScriptID);
            unset(self::$scripts[$ScriptID]);
        }

        public static function setScriptFile(int $ScriptID, string $FilePath): void
        {
            self::$scripts[$ScriptID]['ScriptFile'] = $FilePath;
        }

        public static function setScriptContent(int $ScriptID, string $Content): void
        {
            self::$content[$ScriptID] = $Content;
        }

        public static function scriptExists(int $ScriptID): bool
        {
            return isset(self::$scripts[$ScriptID]);
        }

        public static function checkScript(int $ScriptID): void
        {
            if (!self::scriptExists($ScriptID)) {
                throw new \Exception(sprintf('Script #%d does not exist', $ScriptID));
            }
        }

        public static function getScript(int $ScriptID): array
        {
            self::checkScript($ScriptID);

            return self::$scripts[$ScriptID];
        }

        public static function getScriptList(): array
        {
            return array_keys(self::$scripts);
        }

        public static function getScriptFile(int $ScriptID): string
        {
            self::checkScript($ScriptID);

            return self::$scripts[$ScriptID]['ScriptFile'];
        }

        public static function getScriptContent(int $ScriptID): string
        {
            return self::$content[$ScriptID];
        }

        public static function reset()
        {
            self::$scripts = [];
        }
    }

    class EventManager
    {
        private static $events = [];

        public static function reset()
        {
            self::$events = [];
        }
    }

    class MediaManager
    {
        private static $medias = [];

        public static function createMedia(int $MediaID, int $MediaType): void
        {
            self::$medias[$MediaID] = [
                'MediaID'          => $MediaID,
                'MediaType'        => $MediaType,
                'MediaIsAvailable' => false,
                'MediaFile'        => '',
                'MediaCRC'         => '',
                'MediaIsCached'    => false,
                'MediaSize'        => 0,
                'MediaUpdated'     => 0
            ];
        }

        public static function deleteMedia(int $MediaID, bool $DeleteFile): void
        {
            self::checkMedia($MediaID);
            unset(self::$medias[$MediaID]);
        }

        public static function mediaExists(int $MediaID): bool
        {
            return isset(self::$medias[$MediaID]);
        }

        public static function checkMedia(int $MediaID): void
        {
            if (!self::mediaExists($MediaID)) {
                throw new \Exception(sprintf('Media #%d does not exist', $MediaID));
            }
        }

        public static function reset()
        {
            self::$medias = [];
        }
    }

    class LinkManager
    {
        private static $links = [];

        public static function createLink(int $LinkID)
        {
            self::$links[$LinkID] = [
                'LinkID'   => $LinkID,
                'TargetID' => 0
            ];
        }

        public static function deleteLink(int $LinkID)
        {
            self::checkLink($LinkID);
            unset(self::$links[$LinkID]);
        }

        public static function getLink(int $LinkID)
        {
            self::checkLink($LinkID);
            return self::$links[$LinkID];
        }

        public static function getLinkIdByName(string $LinkName, int $ParentID)
        {
            throw new \Exception('Getting link by name is not implemented yet');
        }

        public static function getLinkList()
        {
            self::checkLink($LinkID);
            return array_keys(self::$links);
        }

        public static function linkExists(int $LinkID)
        {
            return isset(self::$links[$LinkID]);
        }

        public static function setLinkTargetID(int $LinkID, int $TargetID)
        {
            self::checkLink($LinkID);
            self::$links[$LinkID]['TargetID'] = $TargetID;
        }

        public static function reset()
        {
            self::$links = [];
        }

        private static function checkLink(int $LinkID)
        {
            if (!self::linkExists($LinkID)) {
                throw new \Exception(sprintf('Link #%d does not exist', $LinkID));
            }
        }
    }

    class ProfileManager
    {
        private static $profiles = [];

        public static function createVariableProfile(string $ProfileName, int $ProfileType): void
        {
            self::$profiles[$ProfileName] = [
                'ProfileName'  => $ProfileName,
                'ProfileType'  => $ProfileType,
                'Icon'         => '',
                'Prefix'       => '',
                'Suffix'       => '',
                'MaxValue'     => 0,
                'MinValue'     => 0,
                'Digits'       => 0,
                'StepSize'     => 0,
                'IsReadOnly'   => false,
                'Associations' => []
            ];
        }

        public static function deleteVariableProfile(string $ProfileName): void
        {
            self::checkVariableProfile($ProfileName);
            unset(self::$profiles[$ProfileName]);
        }

        public static function setVariableProfileText(string $ProfileName, string $Prefix, string $Suffix): void
        {
            self::checkVariableProfile($ProfileName);

            self::$profiles[$ProfileName]['Prefix'] = $Prefix;
            self::$profiles[$ProfileName]['Suffix'] = $Suffix;
        }

        public static function setVariableProfileValues(string $ProfileName, float $MinValue, float $MaxValue, float $StepSize): void
        {
            self::checkVariableProfile($ProfileName);

            self::$profiles[$ProfileName]['MinValue'] = $MinValue;
            self::$profiles[$ProfileName]['MaxValue'] = $MaxValue;
            self::$profiles[$ProfileName]['StepSize'] = $StepSize;
        }

        public static function setVariableProfileDigits(string $ProfileName, int $Digits): void
        {
            self::checkVariableProfile($ProfileName);

            self::$profiles[$ProfileName]['Digits'] = $Digits;
        }

        public static function setVariableProfileIcon(string $ProfileName, string $Icon): void
        {
            self::checkVariableProfile($ProfileName);

            self::$profiles[$ProfileName]['Icon'] = $Icon;
        }

        public static function setVariableProfileAssociation(string $ProfileName, float $AssociationValue, string $AssociationName, string $AssociationIcon, int $AssociationColor)
        {
            self::checkVariableProfile($ProfileName);

            if (($AssociationName == '') && ($AssociationIcon == '')) {
                unset($keyFound);
                foreach (self::$profiles[$ProfileName]['Associations'] as $key => $association) {
                    if ($association['Value'] == $AssociationValue) {
                        $keyFound = $key;
                        break;
                    }
                }
                if (isset($keyFound)) {
                    unset(self::$profiles[$ProfileName]['Associations'][$keyFound]);
                    usort(self::$profiles[$ProfileName]['Associations'], function ($a, $b)
                    {
                        return $a['Value'] - $b['Value'];
                    });
                } else {
                    trigger_error(sprintf('Cannot find association for deletion with value %f', $AssociationValue), E_USER_WARNING);
                }
                return;
            }

            foreach (self::$profiles[$ProfileName]['Associations'] as &$association) {
                if ($association['Value'] == $AssociationValue) {
                    $association['Name'] = $AssociationName;
                    $association['Icon'] = $AssociationIcon;
                    $association['Color'] = $AssociationColor;
                    return;
                }
            }

            self::$profiles[$ProfileName]['Associations'][] = [
                'Value' => $AssociationValue,
                'Name'  => $AssociationName,
                'Icon'  => $AssociationIcon,
                'Color' => $AssociationColor
            ];

            usort(self::$profiles[$ProfileName]['Associations'], function ($a, $b)
            {
                return $a['Value'] - $b['Value'];
            });
        }

        public static function variableProfileExists(string $ProfileName): bool
        {
            return isset(self::$profiles[$ProfileName]);
        }

        public static function checkVariableProfile(string $ProfileName): void
        {
            if (!self::variableProfileExists($ProfileName)) {
                throw new \Exception(sprintf('Profile #%s does not exist', $ProfileName));
            }
        }

        public static function getVariableProfile(string $ProfileName): array
        {
            self::checkVariableProfile($ProfileName);

            return self::$profiles[$ProfileName];
        }

        public static function getVariableProfileList(): array
        {
            return array_keys(self::$profiles);
        }

        public static function getVariableProfileListByType(int $ProfileType): array
        {
            $result = [];
            foreach (self::$profiles as $profile) {
                if ($profile['ProfileType'] == $ProfileType) {
                    $result[] = $profile;
                }
            }

            return $result;
        }

        public static function reset()
        {
            self::$profiles = [];
        }
    }

    class DebugServer
    {
        private static $debug = [];

        public static function disableDebug(int $ID): void
        {
            self::$debug[$ID] = 0;
        }

        public static function enableDebug(int $ID, int $Duration): void
        {
            self::$debug[$ID] = time() + $Duration;
        }

        public static function sendDebug(int $SenderID, string $Message, string $Data, int $Format): void
        {
            if (!isset(self::$debug[$SenderID])) {
                return;
            }

            if (time() > self::$debug[$SenderID]) {
                return;
            }

            if ($Format == 1 /* Binary */) {
                $Data = bin2hex($Data);
            }

            echo 'DEBUG: ' . $Message . ' | ' . $Data . PHP_EOL;
        }

        public static function reset()
        {
            self::$debug = [];
        }
    }

    class Kernel
    {
        public static function reset()
        {
            ModuleLoader::reset();
            ObjectManager::reset();
            CategoryManager::reset();
            InstanceManager::reset();
            VariableManager::reset();
            ScriptManager::reset();
            EventManager::reset();
            MediaManager::reset();
            LinkManager::reset();
            ProfileManager::reset();
            DebugServer::reset();
        }
    }
}
