<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Utils;

class OptionManager
{
	/**
	 *
	 * @var array
	 */
	private $options;
    
    /**
     * 
     * @var array
     */
    private $optionsDescription;
    
    /**
     * each option may have following properties
     * 
     * short:       string
     * long:        string
     * type:        enum ['', ':']
     * required:    boolean
     * default:     string
     * enum:        string[]
     * description: string
     * 
     * @param array $opts associative array of several options [option_name => options_properties].
     */
    public function registerOptionDesciption(array $opts)
    {
        $names = [];
        $shorts = [];
        $longs = [];
        
        foreach ($opts as $name => $opt) {
            if (in_array($name, $names)) {
                throw new \Exception("several options with same name '$name'");
            }
            $names[] = $name;
            if (isset($opt['short'])) {
                if (in_array($opt['short'], $shorts)) {
                    throw new \Exception("several options with same short name '{$opt['short']}'");
                }
                $shorts[] = $opt['short'];
            }
            if (isset($opt['long'])) {
                if (in_array($opt['long'], $longs)) {
                    throw new \Exception("several options with same long name '{$opt['long']}'");
                }
                $shorts[] = $opt['long'];
            }
            if (!isset($opt['short']) && !isset($opt['long'])) {
                throw new \Exception('error option should have at least \'short\' or \'long\' property');
            }
            if (isset($opt['enum']) && !is_array($opt['enum'])) {
            	throw new \Exception('error \'enum\' should be an array of string');
            }
            if (isset($opt['pattern']) && preg_match("/{$opt['pattern']}/", 'test', $match) === false) {
            	throw new \Exception('error invalid \'pattern\' : ' . $opt['pattern']);
            }
            if (isset($opt['enum']) && isset($opt['pattern'])) {
            	throw new \Exception('error option cannot have \'enum\' and \'pattern\' properties');
            }
            if (!array_key_exists('has_value', $opt)) {
            	throw new \Exception('error missing key \'has_value\'');
            }
            if (!is_bool($opt['has_value'])) {
            	throw new \Exception('error \'has_value\' should be a boolean');
            }
        }
        $this->optionsDescription = $opts;
    }
    
    /**
     * acquire options
     * 
     * @throws \Exception
     * @return string[]
     */
    public function getOptions()
    {
    	if (!is_null($this->options)) {
    		return $this->options;
    	}
        if (is_null($this->optionsDescription)) {
            throw new \Exception('no option registered');
        }
        $options = [];
        
        $shortopts = '';
        $longopts  = [];
        
        foreach ($this->optionsDescription as $opt) {
            $shortopts .= isset($opt['short']) ? ($opt['has_value'] ? $opt['short'].':' : $opt['short']) : '';
            $longopts[] = isset($opt['long']) ? ($opt['has_value'] ? $opt['long'].':' : $opt['long']) : null;
        }
        
        // get options from commande line
        $tmp_options = getopt($shortopts, $longopts);
        
        // grab options with their long name
        foreach ($this->optionsDescription as $name => $opt) {
            $short_option = isset($opt['short']) ? $opt['short'] : null;
            $long_option  = isset($opt['long']) ? $opt['long'] : null;
            
            if (array_key_exists($short_option, $tmp_options)) {
                if (array_key_exists($long_option, $tmp_options)) {
                    echo ('conflict between arguments \'' . $short_option . '\' and \'' . $long_option . "'" . PHP_EOL);
                    exit(1);
                }
                $options[$name] = $tmp_options[$short_option];
            } elseif (array_key_exists($long_option, $tmp_options)) {
                $options[$name] = $tmp_options[$long_option];
            } elseif (isset($opt['default'])) {
                $options[$name] = $opt['default'];
            }
        }
        
        // validate options
        foreach ($this->optionsDescription as $name => $opt) {
            $valid = false;
            while (!$valid) {
                if (isset($options[$name])) {
                    if (isset($opt['enum']) && !in_array($options[$name], $opt['enum'])) {
                        echo "please enter a $name " . (isset($opt['enum']) ? '(must be one of '.json_encode($opt['enum']).') ' : '') . ': ';
                        $options[$name] = trim(fgets(STDIN));
                    } elseif (isset($opt['pattern']) && !preg_match("/{$opt['pattern']}/", $options[$name], $match)) {
                    	echo "please enter a $name " . (isset($opt['pattern']) ? '(must match with pattern \'' . $opt['pattern'] .'\') ' : '') . ': ';
                    	$options[$name] = trim(fgets(STDIN));
                    } else {
                        $valid = true;
                    }
                } elseif (isset($opt['required']) && $opt['required']) {
                    if ($opt['has_value']) {
                        echo "please enter a $name " . (isset($opt['enum']) ? '(must be one of '.json_encode($opt['enum']).') ' : '') . ': ';
                        $options[$name] = trim(fgets(STDIN));
                    } else {
                        throw new \Exception('not valid option definition');
                    }
                } else {
                    $valid = true;
                }
            }
        }
        $this->options = $options;
        return $this->options;
    }
    
    /**
     * get option
     * 
     * @param string $name
     * @return string|null return null if option doesn't exist
     */
    public function getOption($name)
    {
    	$options = $this->getOptions();
    	return array_key_exists($name, $options) ? $options[$name] : null;
    }
    
    /**
     * verify if option is in specified script arguments
     * 
     * @param string $name
     * @return boolean
     */
    public function hasOption($name)
    {
    	$options = $this->getOptions();
    	return array_key_exists($name, $options);
    }
    
    /**
     * get stringified help
     * 
     * @throws \Exception
     * @return string
     */
    public function getHelp() {
        if (is_null($this->optionsDescription))
        {
            throw new \Exception('no option registered');
        }
        $help = "OPTIONS : " . PHP_EOL;
        $first_pad = 0;
        $second_pad = 0;
        foreach ($this->optionsDescription as $opt_name => $opt) {
            if (isset($opt['short'])) {
                $opt_lenght = 5 + ($opt['has_value'] ? strlen($opt_name) : 0) + 3;
                $first_pad = max($first_pad, $opt_lenght);
            }
            if (isset($opt['long'])) {
                $opt_lenght = strlen($opt['long']) + ($opt['has_value'] ? strlen($opt_name) : 0) + 6;
                $second_pad = max($second_pad, $opt_lenght);
            }
        }
        foreach ($this->optionsDescription as $opt_name => $opt) {
            if (!isset($opt['description'])) {
                throw new \Exception('description is missing');
            }
            $line = '';
            if (isset($opt['short'])) {
                $line .= "  -{$opt['short']} " . ($opt['has_value'] ? "<$opt_name>" : '');
            }
            $line = str_pad($line, $first_pad);
            if (isset($opt['long'])) {
                $line .= "--{$opt['long']} " . ($opt['has_value'] ? "<$opt_name>" : '');
            }
            $line = str_pad($line, $first_pad + $second_pad);
            $line .= $opt['description'] . PHP_EOL;
            
            $help .= $line;
        }
        
        $long_descriptions = '';
        foreach ($this->optionsDescription as $opt_name => $opt) {
            $line = '';
            if (isset($opt['short'])) {
                $line .= "  -{$opt['short']} " . ($opt['has_value'] ? "<$opt_name>" : '');
            }
            if (isset($opt['long'])) {
                $line .= "  --{$opt['long']} " . ($opt['has_value'] ? "<$opt_name>" : '');
            }
            $line .= ' : ' . PHP_EOL . '    ';
            if (isset($opt['long_description'])) {
            	$long_descriptions .= PHP_EOL. $line . str_replace(PHP_EOL, PHP_EOL . "    ", $opt['long_description']) . PHP_EOL;
            } elseif (isset($opt['enum'])) {
            	$long_descriptions .= PHP_EOL. $line . 'Value must be one of : ' . PHP_EOL;
            	foreach ($opt['enum'] as $value) {
            		$long_descriptions .= "     - $value" . PHP_EOL;
            	}
            } elseif (isset($opt['pattern'])) {
            	$long_descriptions .= PHP_EOL. $line . 'Value must match with following pattern : ' . PHP_EOL;
            	$long_descriptions .= '    ' . $opt['pattern'] . PHP_EOL;
            }
        }
        if (!empty($long_descriptions)) {
        	$help .=  PHP_EOL . "DETAILS : " . $long_descriptions."END" . PHP_EOL;
        }
        
        return $help;
    }
    
    /**
     * verify if arguments script contain an help option (-h or --help)
     */
    public function hasHelpArgumentOption()
    {
        $help = getopt('h', ['help']);
        return isset($help['h']) || isset($help['help']);
    }
}
