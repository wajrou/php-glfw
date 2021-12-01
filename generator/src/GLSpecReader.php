<?php

class GLSpecReader 
{
    /**
     * Parse the xml at the given path
     * 
     * @param GLSpec            $spec
     * @param string            $pathToXml
     */
    public function parse(GLSpec $spec, string $pathToXml)
    {
        if (!file_exists($pathToXml)) {
            throw new \Exception("GL Spec file could not be loaded at: " . $pathToXml);
        }

        $xml = simplexml_load_file($pathToXml);

        foreach($xml->children() as $el) 
        {
            $name = $el->getName();

            if ($name === 'types') {
                $this->parseTypes($spec, $el);
            } elseif ($name === 'commands') {
                $this->parseCommands($spec, $el);
            } elseif ($name === 'feature') {
                $this->parseFeatures($spec, $el);
            }
        }
    }

    /**
     * Parse an element of types
     * 
     * @param GLSpec                $spec
     * @param SimpleXMLElement      $types
     */
    private function parseTypes(GLSpec $spec, SimpleXMLElement $types)
    {
    }

    /**
     * Parse an element of commands
     * 
     * @param GLSpec                $feature
     * @param SimpleXMLElement      $commands
     */
    private function parseFeatures(GLSpec $spec, SimpleXMLElement $feature)
    {
        $version = $spec->makeVersion($feature->attributes()->name);
        $version->versionString = $feature->attributes()->number;
        $version->api = $feature->attributes()->api;

        // required features
        foreach($feature->require->children() as $cat) 
        {   
            if ($cat->getName() === 'enum') {
                $version->enums[] = (string) $cat->attributes()->name;
            }

            elseif ($cat->getName() === 'command') {
                $version->functions[] = (string) $cat->attributes()->name;
            }
        }
    }

    /**
     * Parse an element of commands
     * 
     * @param GLSpec                $spec
     * @param SimpleXMLElement      $commands
     */
    private function parseCommands(GLSpec $spec, SimpleXMLElement $commands)
    {
        foreach($commands->children() as $commandDef) 
        {
            if (!$commandDef->proto) {
                continue;
            }

            $proto = $commandDef->proto;

            $func = $spec->makeFunction((string)$proto->name);
            $func->class = $proto->attributes()->class;
            $func->group = $proto->attributes()->group;

            $funcPtype = trim((string) $proto->ptype);
            $func->returnTypeString = $funcPtype ?: trim((string) $proto);

            // go over arguments
            foreach($commandDef->param as $argDef) 
            {
                $arg = $func->makeArg();
                $arg->name = (string) $argDef->name;
                $arg->typeString = (string) $argDef->ptype;
                $arg->class = $argDef->attributes()->class;
                $arg->group = $argDef->attributes()->group;
            }
        }
    }
}
