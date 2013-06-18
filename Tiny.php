<?php 

/**
 * Class for outputting HTML for the view layer
 * 
 * This class uses a simple nested template system. The outermost/parent
 * template is usually the HTML skeleton or page structure. Nested/child
 * templates can be HTML fragments that are embedded at the specified place in
 * their parent file.
 * 
 * @author 1/12/2012 Brett Rawlins
 * @version 9/18/2012 Brett Rawlins
 */
class Tiny {
	
	/**
	 * The document root for this site
	 * @var string
	 */
	public $document_root;
    
	/**
	 * The outer/parent template file
	 * @var string
	 */
	public $html;
	
	/**
	 * Variables to pass to the templates
	 * @var array
	 */
	public $vars;
	
	/**
	 * Paths to supporting files
	 * @var array
	 */
	public $paths;
	
	/**
	 * Meta tags for the <head> section
	 * @var array
	 */
	public $meta;
	
	/**
	 * Link tags for the <head> section
	 * @var array
	 */
	public $links;
	
	/**
	 * Script tags for the <head> section
	 * @var array
	 */
	public $scripts;

	/**
	 * Custom content for the <head> section, such as conditional comments
	 * @var string
	 */
	public $custom;

	/**
	 * Content for the <title> tag
	 * @var string
	 */
	public $title;


	/**
	 * Constructor
	 * 
	 * @param string $document_root
	 */	
	public function __construct($document_root = '') 
	{
		// default to $_SERVER['DOCUMENT_ROOT']
		$this->document_root = (!empty($document_root)) ? trim($document_root) : $_SERVER['DOCUMENT_ROOT'];
	} 
	
	/**
	 * Sets the outer/parent html template file
	 * 
	 * @param string $filename - name of the template file
	 */
	public function setHtml($filename) 
	{
		// look in the document root if no html path is set
		if (isset($this->paths['html'])) {
			$this->html = $this->findFile($filename, $this->paths['html']);
		} else {
			$this->html = $this->findFile($filename, $this->document_root);
		}
		
		// report success
		return $this->html;
	}
	
	/**
	 * Sets the path to a group of supporting files (e.g. html, css, js)
	 * 
	 * @param string $name 
	 * @param string $value
	 */
	public function setPath($name, $value) 
	{
		$this->paths[$name] = $value;
	}
	
	/**
	 * Sets a variable to be used in the html
	 * 
	 * @param string $name 
	 * @param string $value
	 */
	public function setVar($name, $value) 
	{
	    $this->vars[$name] = $value;
	}
	
    /**
     * Sets an array of variables to be used in the html
     * 
     * @param array $array - associative array of name => value pairs for the variables
     */
    public function setVars($array)
    {
    	if (empty($array) || !is_array($array)) {
	    	return FALSE;
    	}
    	
    	foreach ($array as $name => $value) {
    		$this->vars[$name] = $value;
    	}
    }
	
	/**
	 * Sets content for the HTML <head> section
	 * 
	 * @param string $name 
	 * @param mixed $value - string or array
	 * @param boolean $value_contains_path - allows you to bypass defined paths by giving the path in the value
	 */
	public function setHead($name, $value, $value_contains_path = FALSE) 
	{
		// get the file path if applicable
		$path = $this->getPath($name);
		
    	switch ($name) 
    	{
    		// css file
			case 'css':
				if (is_array($value)) {
					$arr = $value;
					$arr['rel'] = 'stylesheet';
				} else {
					$arr = array('rel' => 'stylesheet', 'href' => $value);
				}
				$arr['href'] = $this->findFile($arr['href'], $path, TRUE); // root-relative path
				$this->links[] = $arr;
				break;
				
			// <link> tag
			case 'link':
				if (!is_array($value) || !isset($value['href'])) {
					return FALSE;
				} 
				$value['href'] = $this->findFile($value['href'], '/view', TRUE); // root-relative path
				$this->links[] = $value;
				break;
				
			// javascript file
			case 'js':
				if ($value_contains_path) {
					// get directory
					$file = substr(strrchr($value, '/'), 1); 
					$directory = dirname($value);
					$this->scripts[] = $this->findFile($file, $directory, TRUE); // root-relative path
				} else {
					$this->scripts[] = $this->findFile($value, $path, TRUE); // root-relative path
				}
				break;
				
			// <meta> tag
			case 'meta';
				$this->meta[] = $value;
				break;
				
			// <title> tag	
			case 'title':
				$this->title = $value;
				break;
				
			// custom content for the <head> section; it will be echoed verbatim	
			case 'custom';
				$this->custom[] = $value;
				break;
		}		
	}
	
	/**
	 * Displays the contents of the HTML <head> section
	 */
	public function displayHead() 
	{
		$this->displayTitle();
		$this->displayMeta();
		$this->displayLinks();
		$this->displayScripts();
		$this->displayCustom();
	}
	
	/**
	 * Displays the <title> tag
	 */
	protected function displayTitle() 
	{
		if (isset($this->title)) {
			echo '<title>' . $this->title . '</title>' . PHP_EOL;
		}
	}
	
	/**
	 * Displays all the <meta> tags that were set
	 */
	protected function displayMeta()
	{
		if (empty($this->meta) || !is_array($this->meta)) {
			return FALSE;
		}
		
		foreach ($this->meta as $meta) 
		{
			// handle special cases
			if ($meta['name'] == 'charset') {
				echo '<meta charset="' . $meta['content'] . '">' . PHP_EOL;
			} elseif ($meta['name'] == 'http-equiv') {
				$arr = each($meta['content']);
				echo '<meta http-equiv="' . $arr['key'] . '" content="' . $arr['value'] . '">' . PHP_EOL;
			} else { // default
				echo '<meta name="' . $meta['name'] . '" content="' . $meta['content'] . '">' . PHP_EOL;
			}
		}
	}
	
	/**
	 * Displays all the <link> tags that were set
	 */
	protected function displayLinks()
	{
		if (empty($this->links) || !is_array($this->links)) {
			return FALSE;
		}

		foreach ($this->links as $item) 
		{
			// make sure we have what we need
			if (!is_array($item) || empty($item['rel']) || empty($item['href'])) {
				continue;
			}
			
			// display the link
			$link = '<link rel="' . $item['rel'] . '"';
			unset($item['rel']); // so we don't display it again in the loop
			foreach ($item as $key => $value) {
				$link .= ' '.$key.'="'.$value.'"';
			}
			$link .= '>' . PHP_EOL;
			
			echo $link;
		}
	}
	
	/**
	 * Displays all the <script> tags that were set
	 */
	protected function displayScripts()
	{
		if (empty($this->scripts) || !is_array($this->scripts)) {
			return FALSE;
		}

		foreach ($this->scripts as $file) 
		{
			// make sure we have what we need
			if (empty($file) || !is_string($file)) {
				return FALSE;
			}
			
			echo '<script src="' . $file . '"></script>' . PHP_EOL;
		}
	}
	
	/**
	 * Displays custom meta verbatim
	 * Can be used to add conditional comments or any custom content to the head area. Content string is echoed verbatim.
	 */
	protected function displayCustom()
	{
		if (empty($this->custom) || !is_array($this->custom)) {
			return FALSE;
		}
		
		foreach ($this->custom as $meta) 
		{
			// just echo the content
			echo $meta . PHP_EOL;
		}
	}
	
	/**
	 * Includes the given html template
	 * 
	 * The file is embedded at that point in its parent template. If you need to re-use a variable name that
	 * was already set for a parent template, you can override it by passing in an array of variables to this template.
	 * 
	 * @param string $file
	 * @param array $vars - an associative array of variables to pass to this template
	 */
	public function includeHtml($file, $vars = NULL) 
	{
		// exit if no file given
		if (empty($file)) {
			return FALSE;
		}
		
		// look in the document root if no html path is set
		if (isset($this->paths['html'])) {
			$file = $this->findFile($file, $this->paths['html']);
		} else {
			$file = $this->findFile($file, $this->document_root);
		}
		
		// make sure the file exists
		if (empty($file) || !file_exists($file)) {
			return FALSE;
		}
		
		// put class variables in local scope for template
		extract($this->vars);
		
		// overwrite any collisions if vars were passed in
		if (isset($vars)) {
			extract($vars, EXTR_OVERWRITE);
		}
		
		if (file_exists($file)) {
			include $file;
		}
	}
	
	/**
	 * Outputs all the HTML
	 * The outer template is included, which can include other nested templates. 
	 */
	public function render() 
	{
		// make sure we have a template to display
		if (empty($this->html) || !file_exists($this->html)) {
			return FALSE;
		}
		
		// start buffer
		ob_start();
		
		// put class variables in local scope for template
		extract($this->vars);
		if (file_exists($this->html)) {
			include $this->html;
		}
		
		// flush buffer
		ob_end_flush();
	}

	/**
	 * Returns the HTML so it can be stored in a variable
	 */
	public function getHtml() 
	{
		// make sure we have a template to display
		if (empty($this->html) || !file_exists($this->html)) {
			return FALSE;
		}
		
		// start buffer
		ob_start();
		
		// put class variables in local scope for template
		extract($this->vars);
		
		// capture the buffer contents
		if (file_exists($this->html)) {
			include $this->html;
		}
		$html = ob_get_contents();
		ob_end_clean();
		
		// return it
		return $html;
	}
	
	/**
	 * Clears all variables and the html template
	 */
	public function clear()
	{
		$this->html = NULL;
		$this->vars = array();
	}
	
	/**
	 * Searches for a file under the given directory and its subdirectories
	 * 
	 * Returns the file path or false if not found. The directory must be
	 * either a file path relative to the document root, or a full URL that
	 * supports directory listing (Apache directive)
	 * 
	 * @param string $filename - file to look for
	 * @param string $directory - where to look for it (defaults to the document root)
	 * @param bool $return_relative_path - flag to return a path relative to the document root
	 */
	public function findFile($filename, $directory = '', $return_relative_path = FALSE)
	{
		// if it's a URL, we already have the path so we're good
		if (preg_match('/^http/i', $filename)) {
			return $filename;
		}

		// default to document root if no path given		
		if (empty($directory)) {
			$directory = $this->document_root;
		} else {
			// otherwise, assume the path is relative to the document root, so we'll remove it (in case it's already there) and then add it back.
			// remove document root, and leading and trailing slashes
			$directory = preg_replace('#'.$this->document_root.'#', '', $directory);
			$directory = preg_replace('#\/$#', '', $directory);
			$directory = preg_replace('#^\/#', '', $directory);
			$directory = $this->document_root.'/'.$directory;
		}
		
		// get all the files in that directory
		$files = $this->getFiles($directory);
		if (empty($files)) {
			return FALSE;
		}	

		// loop through them until we find the one we're looking for		
		foreach ($files as $file) {
			// compare the filenames
			if (strcmp(basename($file), $filename) == 0) {
				if ($return_relative_path) {
					// return relative path
					$file = preg_replace('#'.$this->document_root.'#', '', $file);
					return $file;
				} else {
					// return absolute path
					return $file;
				}
			}
		}
				
		// not found
		return FALSE;
	}

	/**
	 * Returns an array of all the files in a directory
	 * 
	 * @param string $directory - the direcory to search
	 * @param bool $recursive - recursively search subdirectories
	 */
	public function getFiles($directory, $recursive = TRUE)
	{
		// initialize return
		$files = array();
		
		// loop through the directory 
		if ($handle = @opendir($directory))
		{
			while (($file = readdir($handle)) !== FALSE) 
			{
				// skip . files
				if (preg_match('/^\./', $file)) {
					continue;
				}
				
				// get the full path
				$file = $directory . '/' . $file;
			
				if (is_dir($file) && $recursive) {
					// re-loop
					$files = array_merge($files, $this->getFiles($file, TRUE));
				} else {
					// add it
					$files[] = $file;		
				}
			}
		}
		
		return $files;
	}

	/**
	 * Returns the given path if set, or document root otherwise
	 * 
	 * @param string $name
	 */	
	protected function getPath($name)
	{
		// default to document root if path not set
		if (!empty($this->paths[$name])) {
			return $this->paths[$name];
		} else {
			return $this->document_root;
		}
	}
	
	 
}
