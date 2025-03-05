<?php
/**
 * This file sets the content type to plain text with Shift_JIS charset and prints '0ﾂ･rﾂ･n'.
 *
 * @package Metaps Response html
 */

header( 'Content-Type: text/plain; charset=Shift_JIS' );
print '0ﾂ･rﾂ･n';
