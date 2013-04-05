# mtDoctrineExtraPlugin

This is a symfony 1.4 plugin for some Doctrine 1.2 related extensions.

## Behaviors

### mtSluggable

Rewritten version of the original Sluggable behavior with the purpose of to be more clearer and flexible.

Usage:

    # schema.yml
    Article:
      actAs:
        mtSluggable:
          fields: [title]
          uniqueBy: [language]
      columns:
        title:
          type: string(255)
          notnull: true
        language:
          type: string(6)
          notnull: true

Available options (with default values):

    // column definition
    'name'      => 'slug',
    'alias'     => null,
    'type'      => 'string',
    'length'    => 255,
    'options'   => array(),

    // index definition
    'indexName' => null,
    'uniqueBy'  => array(),
    'unique'    => true,

    // listener options
    'listener'  => 'mtSluggableListener',
    'fields'    => array(), // fields used by the slug
    'canUpdate' => true,
    'provider'  => null, // provide a string to slugify
    'builder'   => null, // create an URL friendly slug

The template comes with the following methods, which can be called through
the model object.

    chopSlug
    disableSluggableListener
    enableSluggableListener
    generateSlug
    getSimilarSlugs
    getSlugDefaultValue
    getSlugFieldLength
    getSlugFieldName
    getSluggableFields
    getSlugValue
    isSluggableFieldModified
    isSlugModified
    isSlugShouldBeRegenerated
    makeUniqueSlug
    provideSluggableValue
    setSlugValue
    slugify
    updateSlug

If you want to modify the slug creation process you should override the following
methods at will.

    chopSlug
    generateSlug
    getSimilarSlugs
    isSluggableFieldModified
    isSlugShouldBeRegenerated
    makeUniqueSlug
    provideSluggableValue
    slugify
    updateSlug

### mtCountCache

Caches related record count for one-to-many relations into the inverse side table.

Usage:

    # schema.yml
    Product:
      columns:
        name:
          type: string(255)
          notnull: true

    ProductImage:
      actAs:
        mtCountCache:
          relations:
            Product: ~
      columns:
        product_id:
          type: integer
          notnull: true
        image:
          type: string(255)
          notnull: true
      relations:
        Product:
          foreignAlias: Images
          onDelete: CASCADE

Available options (with default values):

    'relations' => array(),
    'listener'  => 'mtCountCacheListener',

    // under the relations key you can specify
    'foreignAlias' // trying to figure out if not given
    'columnName' // number_of_$foreignAlias
