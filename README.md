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

### mtSortable (MySQL only)

Makes records sortable by adding an order column to the table. It's a very simple
implementation and works only with MySQL. Always reorders the whole table so it
can be slow with very big tables.

Usage:

    # schema.yml
    Product:
      actAs:
        mtSortable: ~
      columns:
        name:
          type: string(255)
          notnull: true

It can be used in a doctrine based admin generator module easily (it requires
[jQueryUI Sortable](http://jqueryui.com/sortable/) extension which should be
loaded in already).

    # generator.yml
    generator:
      class: sfDoctrineGenerator
      param:
        model_class:           Product
        # ...
        actions_base_class:    BaseMtSortableActions

    # routing.yml
    product:
      class: sfDoctrineRouteCollection
      options:
        model:                Product
        # ..
        collection_actions:
          # add these routes
          sort: [get]
          order: [post]

    // sortSuccess.php
    <?php use_helper('I18N', 'Date') ?>
    <?php include_partial('Product/assets') ?>

    <div id="sf_admin_container">
      <h2 class="mbl">
        <?php echo __('Sort Products', array(), 'messages') ?>
      </h2>
      <?php include_partial('Product/flashes') ?>
      <div id="sf_admin_header"></div>
      <div id="sf_admin_content">

        <?php // include this partial for rendering a sortable list and the required javascript ?>
        <?php include_partial('mtSortable/sort', array('objects' => $objects, 'route' => 'product_order')) ?>

        <div class="form-actions">
          <?php echo $helper->linkToList(array('class_suffix' => 'list', 'label' => 'Back to list')) ?>
        </div>
      </div>
      <div id="sf_admin_footer"></div>
    </div>

Available options (with default values):

    // column definition
    'name'       => 'order',
    'alias'      => null,
    'type'       => 'integer',
    'length'     => 1,
    'options'    => array(
      'unsigned' => true,
    ),

    // index definition
    'indexName' => null,

This extension adds the `updateOrder(array $order)` and `queryOrdered` methods
to the table class and `getOrderValue`, `setOrderValue` and `getOrderFieldName`
methods to the record class.

You can customize how records appear in the sortable list by adding a
`getSortableName` method to the record class.
