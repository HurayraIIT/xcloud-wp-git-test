<?php
/**
 * File info for a translation source PO
 */
$this->extend('info');
$this->start('header');
/* @var Loco_mvc_FileParams $file */
/* @var Loco_mvc_FileParams $locale */
/* @var Loco_gettext_Metadata $meta */
?> 

    <div class="panel panel-info">
        <nav>
            <a class="icon only-icon icon-cog" title="Configure" href="<?php $file->e('configure')?>"><span>configure</span></a>
            <a class="icon only-icon icon-download" title="Download" href="<?php $file->e('download')?>"><span>download</span></a>
        </nav>
        <h3>
            <a href="<?php $locale->e('href')?>" class="has-lang">
                <span class="<?php $locale->e('icon')?>" lang="<?php $locale->e('lang')?>"><code><?php $locale->e('code')?></code></span>
                <span><?php $locale->e('name')?></span>
            </a>
        </h3>
        <dl>
            <dt><?php self::e( __('File size','loco-translate') )?>:</dt>
            <dd><?php $file->e('size')?></dd>

            <dt><?php self::e( __('File modified','loco-translate') )?>:</dt>
            <dd><time><?php $file->date('mtime')?></time></dd>

            <dt><?php self::e( __('Last translation','loco-translate') )?>:</dt>
            <dd><?php $params->e('author')?> &mdash; <date><?php $params->date('potime')?></date></dd>
            
            <dt><?php self::e( __('Translation progress','loco-translate') )?>:</dt>
            <dd>
                <?php self::e( $meta->getProgressSummary() )?> 
            </dd>
            <dd>
                <?php $meta->printProgress()?> 
            </dd>
        </dl>
    </div>
 
    <?php
    if( ! $sibling->existent ):?> 
    <div class="panel panel-warning">
        <h3 class="has-icon">
            <?php self::e( __('Binary file missing','loco-translate') )?> 
        </h3>
        <p>
            <?php self::e( __("We can't find the binary MO file that belongs with these translations",'loco-translate') )?>.
        </p>
    </div><?php
    endif;


    if( $params->has('potfile') ):
    if( $potfile->synced ):?> 
    <div class="panel panel-success">
        <h3 class="has-icon">
            <?php self::e( __('In sync with template','loco-translate') )?> 
        </h3>
        <p>
            <?php // Translators: Where %s is the name of a template file
            self::e( __('PO file has the same source strings as "%s"','loco-translate'), $potfile->name )?>.
        </p>
    </div><?php

    else:?> 
    <div class="panel panel-info">
        <h3 class="has-icon">
            <?php self::e( __('Out of sync with template','loco-translate') )?> 
        </h3>
        <p>
            <?php // Translators: Where %s is the name of a template file
            self::e( __('PO file has different source strings to "%s". Try running Sync before making any changes.','loco-translate'), $potfile->name )?> 
        </p>
    </div><?php
    endif;
    
    // only showing missing template warning when project was matched. Avoids confusion if something went wrong
    elseif( $params->has('project') ):?> 
    <div class="panel panel-debug">
        <h3 class="has-icon">
            <?php self::e( __('Missing template','loco-translate') )?> 
        </h3>
        <p>
            <?php
            self::e( __('These translations are not linked to a POT file. Sync operations will extract strings directly from source code.','loco-translate') )?> 
        </p>
    </div><?php
    endif;
