<?php defined('SYSPATH') or die('No direct script access.');

/**
 * The html decorator class for Formo
 *
 * @package  Formo
 */
class Formo_Decorator_Html_Core extends Formo_Decorator {

	/**
	 * List of HTML tags without closing tags
	 *
	 * (default value: array('br', 'hr', 'input'))
	 *
	 * @var array
	 * @access protected
	 */
	protected $singles = array('br', 'hr', 'input');

	/**
	 * Decorator-specific variables. All accessible thorugh __get($var)
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $_vars = array
	(
		'tag'		=> NULL,
		'attr'		=> array(),
		'classes'	=> array(),
		'css'		=> array(),
		'text'		=> NULL,
		'data'		=> array(),
	);


	public function __isset($var)
	{
		return array_key_exists($var, $this->_vars);
	}

	public function __get($var)
	{
		if (array_key_exists($var, $this->_vars))
			return $this->_vars[$var];
	}

	public function set($var, $value)
	{
		if (func_num_args() === 3)
		{
			$this->_vars[$var][$value] = func_get_arg(2);
		}
		else
		{
			$this->_vars[$var] = $value;
		}

		return $this;
	}

	public function get($var, $default = NULL)
	{
		if (array_key_exists($var, $this->_vars))
			return $this->_vars[$var];

		return $default;
	}

	public function add_var($var, $key, $value)
	{
		$this->_vars[$var][$key] = $value;
	}

	public function remove_var($var, $key)
	{
		unset($this->_vars[$var][$key]);
	}

	// Get or set an html attribute
	public function attr($attr, $value = NULL)
	{
		if (is_array($attr))
		{
			foreach ($attr as $_attr => $_value)
			{
				$this->attr($_attr, $_value);
			}

			return $this;
		}

		if ($attr == 'class')
			return $this->classes($value, func_get_args() < 2);

		if (func_num_args() < 2)
			return Arr::get($this->attr, $attr, NULL);

		if ($value === NULL)
		{
			// Remove the attribute tag only if the value is NULL
			// Empty strings remain empty attributes
			unset($this->_vars['attr'][$attr]);
		}
		else
		{
			$this->set('attr', $attr, $value);
		}

		return $this;
	}

	// Set or retrieve class
	public function classes($class, $retrieve = FALSE)
	{
		if ($retrieve === TRUE)
			// The value of classes the string
			return implode(' ', $this->classes);

		if (in_array($class, $this->classes))
			// No need re-add a class
			return $this;

		// Add the class
		$this->_vars['classes'][] = $class;

		return $this;
	}

	// Set or get a "style" attribute
	public function css($style, $value = NULL)
	{
		if (is_array($style))
		{
			foreach ($style as $_style => $_value)
			{
				$this->css($_style, $_value);
			}

			return $this;
		}

		if (func_num_args() < 2)
			return (isset($this->_css[$style])) ? $this->_css[$style] : NULL;

		if ( ! $value)
		{
			unset($this->_vars['css'][$style]);
		}
		else
		{
			$this->set('css', $style, $value);
		}

		return $this;
	}

	// Add class to element
	public function add_class($class)
	{
		if (is_array($class))
		{
			foreach ($class as $_class)
			{
				$this->add_class($_class);
			}

			return $this;
		}
		elseif (strpos($class, ' ') !== FALSE)
		{
			foreach (explode(' ', $class) as $_class)
			{
				$this->add_class($_class);
			}
		}

		return $this->classes($class);
	}

	// Remove a class if it exists
	public function remove_class($class)
	{
		if ($key = array_search($class, $this->_classes) !== FALSE)
		{
			$this->remove_var('classes', $key);
		}

		return $this;
	}

	// Retrieve the label text
	public function label()
	{
		return ($label = $this->container->get('label'))
			? $label
			: $this->container->alias();
	}

	// Set or return the text
	public function text()
	{
		// Return the text if nothing was entered
		if (func_num_args === 0)
			return $this->_text;

		// Fetch the args
		$vals = func_get_args();

		if (is_array($vals[0]))
		{
			foreach ($vals[0] as $key => $val)
			{
				if (is_string($key))
				{
					$this->text($key, $val);
				}
				else
				{
					$this->text($val);
				}
			}

			return $this;
		}

		// If two args were given perform special functions
		if (count($vals) == 2)
		{
			switch($vals[0])
			{
			case '.=':
				$this->text .= $vals[1];
				break;
			case '=.':
				$this->text = $vals[1].$this->_vars['text'];
				break;
			case 'callback':
				$this->text = call_user_func($vals[1], $this->_vars['text']);
				break;
			}

			return $this;
		}

		// If one arg was given set text to that value
		(count($vals) == 1 AND $this->text = $vals[0]);

		return $this;
	}

	// Turn attributes into a string (tag="val" tag2="val2")
	protected function attr_to_str()
	{
		$classes_str = implode(' ', $this->classes);

		// Begin with _classes
		$str = $classes_str
			? " class=\"$classes_str\""
			: NULL;

		foreach ($this->attr as $attr => $value)
		{
			$valsue = HTML::entities($value);
			$str.= " $attr=\"$value\"";
		}

		// Then attach styles
		if ($this->css)
		{
			$str.= ' style="';
			foreach ($this->css as $style => $value)
			{
				$str.= "$style:$value;";
			}
			$str.= '"';
		}

		return $str;
	}

	// Allows just the opening tag to be returned
	public function open()
	{
		$singletag = in_array($this->tag, $this->singles);

		// return the string tag
		return '<'
			 . $this->tag
			 . $this->attr_to_str()
			 . (($singletag === TRUE)
			    // Do not end the tag if it's a single tag
			    ? '/'
			    // Otherwise close the tag
			    : ">\n");
	}

	// Allows just the closing tag to be returned
	public function close()
	{
		$singletag = in_array($this->tag, $this->singles);

		return (($singletag === FALSE)
					// If the tag is not a single tag, start with a carrot
					? '<'
					: NULL)
				// All closing tags will have the trailing slash
				. '/'
				. (($singletag === FALSE)
					// Non-single tags close again with the tag name
					? $this->tag
					: ((Kohana::config('formo')->close_single_html_tags === TRUE)
						// Let the config file determine whether to close the tags
						? ' /'
						: NULL))
				// Close the tag
				. ">\n";
	}

	public function pre_render()
	{
		if (method_exists($this->driver, 'html') === FALSE)
			return;
			
		// Run the html() setup method if it's defined in the driver
		$this->driver->html();
	}

	public function generate($view, $prefix)
	{
		// If prefix is a string, rtrim it
		($prefix and $prefix = rtrim($prefix, '/'));

		$view = View::factory("$prefix/$view")
			->set($this->driver->alias, $this->container);

		if ($prefix !== FALSE)
		{
			$view
				->set('open', View::factory("$prefix/_open_tag", array('field' => $this->container)))
				->set('close', View::factory("$prefix/_close_tag", array('field' => $this->container)))
				->set('message', View::factory("$prefix/_message", array('field' => $this->container)))
				->set('label', View::factory("$prefix/_label", array('field' => $this->container)));
		}

		return $view;
	}

	// Render fields as html
	public function __toString()
	{
		return $this->render();
	}

	// Return rendered element
	public function render()
	{
		$singletag = in_array($this->tag, $this->singles);

		$str = $this->open();

		if ( ! $singletag)
		{
			$str.= $this->text;
			foreach ($this->container->fields() as $field)
			{
				$args = func_get_args();
				$str.= call_user_func_array(array($field, 'render'), $args);
			}
		}

		return $str.= $this->close();
	}
}
