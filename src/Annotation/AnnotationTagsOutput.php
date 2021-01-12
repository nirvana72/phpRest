<?php
namespace PhpRest\Annotation;

use phpDocumentor\Reflection\DocBlock\Tag;
use phpDocumentor\Reflection\DocBlock\Tags\Formatter;

class AnnotationTagsOutput implements Formatter
{
    /**
     * Formats a tag into a string representation according to a specific format, such as Markdown.
     *
     * @param Tag $tag
     *
     * @return string
     */
    public function format(Tag $tag) : string 
    {
        $this->tags[] = $tag;
        return strval($tag);
    }

    public $tags = [];
}