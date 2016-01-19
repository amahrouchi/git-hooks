#!/usr/bin/env php
<?php
define('CTAG_FILENAME', 'ctags');

class LineParser
{
    protected $line;

    public function __construct($line)
    {
        $this->line = $line;
    }

    public function extractTag()
    {
        $info = explode("\t", $this->line);
        return $info[0];
    }

    public function extractPath()
    {
        $info = explode("\t", $this->line);
        return $info[1];
    }

    public function extractDeclaration()
    {
        $regex = '#/^(.*)\$/;"#';
        preg_match($regex, $this->line, $matches);
        return isset($matches[1]) ? trim( $matches[1] ) : '';
    }

    public function extractType()
    {
        $regex = '#\$/;"(.*)$#';
        preg_match($regex, $this->line, $matches);
        return isset($matches[1]) ? trim($matches[1]) : '';
    }

    public function extractCleanTag()
    {
        $tag      = $this->extractTag();
        $split    = explode('_', $tag);

        return count($split) ? $split[count($split) - 1] : '';
    }

    public function extractAll()
    {
        return array(
            'tag'         => $this->extractTag(),
            'path'        => $this->extractPath(),
            'declaration' => $this->extractDeclaration(),
            'type'        => $this->extractType(),
            'cleanTag'    => $this->extractCleanTag(),
        );
    }

}

// Get current working directory
$currDir = getcwd();

// The CWD is our GIT repo so we can use the alias '.' to specify current dir
echo "Creating tags...\n";
$cmd = 'ctags '.$currDir.' 2> /dev/null'; // Buy using getcwd(), tags paths will be absolute
exec($cmd);

$ctagsPath = $currDir.'/ctags';
if(file_exists($ctagsPath))
{
    echo "Parsing tag file\n";

    $file = new SplFileObject($ctagsPath, 'a+');

    $newFileStr = '';
    while(!$file->eof())
    {
        // Get current line
        $line = $file->fgets();

        // Check empty lines
        if(!isset($line[0]))
            continue;

        // Add comment
        if($line[0] === '!')
        {
            $newFileStr .= $line;
            continue;
        }

        // Parse current line
        $parser = new LineParser($line);
        $info   = $parser->extractAll();

        // Class mapping
        if($info['type'] == 'c')
        {
            // Check PHP extension
            $pathRegex = '#(?<!\.old[0-9]|\.old)\.php$#';
            if(!preg_match($pathRegex, $info['path']))
                continue;

            // Comparing filename and tag name
            $filename = basename($info['path']);
            if($filename === $info['cleanTag'].'.php')
                $newFileStr .= $line;
        }
        else
            $newFileStr .= $line;
    }
    $file = null;

    // Write final file
    $file = new SplFileObject($ctagsPath, 'w');
    $file->fwrite($newFileStr);
    $file = null;
}
else
    echo "No tag file to parse\n";
