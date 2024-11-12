# Image Link Formatter

[[_TOC_]]

## Introduction

The `image_link_formatter` module allows displaying an image wrapped within a
link provided by a custom field.

The module stems from discussions around a requested feature to allow an image
field to be displayed with a link to a custom URL:

- [Add a link option to an image field][1]
- [#1570072: Ability to customize image links][2]

## Installation and configuration

<img src="https://www.drupal.org/files/project-images/201211101520-imagelink_formatter-configuration-0.jpg"
width="50%" align="right" style="margin-left:15px;">

1. Prerequisites:\
Requires the [Image][3] and [Link][4] Core modules to be enabled.

1. Installation:\
Install with `composer` or download the module and copy it into your
contributed modules folder (for example, `/modules/contrib/`) and enable it
from the modules administration page _(requires the image and link modules to be
enabled)_.\
More information at: [Extending Drupal: Installing Modules][5].

1. Configuration ([see screenshot on the right][6]):\
After successful installation, browse to the [Manage Display][7] settings page,
for the entity (Node content type, for example) with an image and a link field,
for example, for the `page` content type the path should be:
`/admin/structure/types/manage/page/display`.\
&nbsp;\
For the image field, select the formatter `Image wrapped within link field` and
edit its settings form, then choose in the `Link image to` dropdown the link
field to use to wrap the image.

## Usage example

<img src="https://www.drupal.org/files/project-images/201211101520-imagelink_formatter-display-0.jpg"
width="50%" align="right" style="margin-left:15px;">

A pretty cool and simple setup or application of this module would be, for
example, to build a _simple ads block_, with an image linking to a custom URL in a
block:\
Add a _Custom block_ type (at `/admin/structure/block/block-content/types`) with
Image and Link fields. Then configure the display formatter of the image field
in the block type to display wrapped in the link (Image Link Formatter).\
On top of that, add Paragraphs and Translation (Core) to have multiple images
with links, in multiple languages, which pretty much rounds this up (flexible
and granular configuration of blocks with image links).

## Implementation

Although new [hooks were introduced to extend field formatters and widgets with
third-party settings][8] with Drupal 8, it still doesn't seem to be possible
to alter existing formatters settings forms (defined by other modules).
Therefore, to alter core `image` formatter's settings form, the only options
would be to either override the `class` in its plugin definition (like it's done for
[SVG image][9]) or create a new formatter.

To avoid potential conflicts with other formatters overriding plugin's class,
we opted for the second option: the module adds the new image field formatter
`image_link_formatter` which also corresponds to how it worked on D7.

However, with D8's new object oriented structure, it is now possible to extend
the core formatter class [ImageFormatter][10], which avoids any code
duplication, reduces the amount of code needed, increases compatibility with
core and improves module's overall maintainability.\
The approach taken in this implementation was to try to extend and use as much
as possible the core formatter and add the logic for the link fields.

In short, this implementation is a port of the D7 module (works the same), but
instead of copying core formatter's code, it extends it.

## Integration

### With other modules

This module plays well and has been tested with any entity provided by Core,
such as [Custom block][11] (`block_content`), User, Taxonomy Terms, etc... or
contributed modules such as [Paragraphs][12].

### With other image formatters

Since this module adds its own image formatter called `image_link_formatter`, it
avoids any potential conflicts with [other formatters that could be overriding or
extending core formatter's class][15].\
However, unless it becomes possible to dynamically extend formatter classes,
unfortunately, at the moment, it doesn't seem possible to _"combine"_ several
formatters for a given field display.\
For example, it doesn't seem possible at the moment, to combine out-of-the-box
the formatters from `svg_image` and `image_link_formatter` to wrap an SVG image
within a link, in which case another formatter combining both would have to be
implemented, see: `enhanced_image_formatter`.

### Link field settings

it should integrate fine with modules altering the link
field, such as [Link attributes][13] or [Link target][14], since attributes are
carried over and displayed in the link wrapping the image.\
For example, if the field is configured to allow user to select to open the link
URL in a new window, then this setting will also be applied to the image link.

### Multiple values

Multiple field instances will work based on field delta. For example:\
Value `0` of field link will be wrapped around value `0` of field
image.\
Value `1` of field link will be wrapped around value `1` of field image.... and
so forth.

## Similar modules

- [Enhanced image formatter][16]: Combines the modules [Svg Image][17] and this
one to wrap formatter's display output within a link provided by a field.
- It seems this function could also be achieved by using [Custom Formatters][18]
(See module's tracker page for more information, image with custom link
formatter is a recurring topic).
- Another solution would be to use the [Linked Field][19] module which pretty
much allows linking any field to custom URL targets with Token patterns.

## Extending plugin classes

To satisfy PhpStan code quality checks of the plugin classes
`ImageLinkFormatter` and/or `ResponsiveImageLinkFormatter`, their constructor
methods was made `final`, preventing child classes from overriding their
`_construct` methods and thus disallowing their dependencies from being changed
the typical Drupal way.\
However, it is still possible to add new dependencies in a child class by using
a `create()` method with the following pattern example:

````php
  /**
  * {@inheritdoc}
  */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $plugin */
    $plugin = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $plugin->currentUser = $container->get('current_user');
    return $plugin;
  }
````

Better yet, instead of using a direct assignment to inject a dependency, it is
recommended to add a set of `set` and `get` methods for the corresponding
service in the extending plugin class, modifying our example above with the
following pattern:

````php
  /**
   * Returns the current user to use for this plugin.
   *
   * @return \Drupal\Core\Session\AccountProxyInterface
   *   The current user.
   */
  public function getCurrentUser() {
    return $this->currentUser ?: \Drupal::currentUser();
  }

  /**
   * Sets the current user to use for this plugin.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user to use for this plugin.
   *
   * @return $this
   */
  public function setCurrentUser(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
    return $this;
  }

  /**
  * {@inheritdoc}
  */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $plugin */
    $plugin = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $plugin->setCurrentUser($container->get('current_user'));
    return $plugin;
  }

````

This allows the child class to be able to utilize the parent `create()` method
while also adding new dependencies to the child class without calling or
overwriting the constructor of the parent class.\
For more information on this particular issue, see ticket #3412951.

## Support and maintenance

Releases of the module can be requested or will generally be created based on
the state of the development branch or the priority of committed patches.

Feel free to follow up in the [issue queue][20] for any contributions, bug
reports, feature requests. Create a ticket in module's issue tracker to describe
the problem encountered, document a feature request or upload a patch.\
Any contribution is greatly appreciated.

[1]: http://drupal.org/node/1543448
[2]: https://www.drupal.org/project/drupal/issues/1570072
[3]: https://www.drupal.org/docs/core-modules-and-themes/core-modules/image-module
[4]: https://www.drupal.org/docs/8/core/modules/link
[5]: https://www.drupal.org/docs/extending-drupal/installing-modules
[6]: https://www.drupal.org/files/project-images/201211101520-imagelink_formatter-configuration-0.jpg
[7]: https://www.drupal.org/docs/user_guide/en/structure-content-display.html
[8]: https://www.drupal.org/node/2130757
[9]: https://git.drupalcode.org/project/svg_image/-/blob/8.x-1.15/svg_image.module#L17
[10]: https://api.drupal.org/api/drupal/core%21modules%21image%21src%21Plugin%21Field%21FieldFormatter%21ImageFormatter.php/class/ImageFormatter/8.9.x
[11]: https://www.drupal.org/docs/user_guide/en/block-create-custom.html
[12]: https://drupal.org/project/paragraphs
[13]: https://www.drupal.org/project/link_attributes
[14]: https://www.drupal.org/project/link_target
[15]: http://grep.xnddx.ru/search?text=%24info%5B%27image%27%5D%5B%27class%27%5D&filename=
[16]: https://www.drupal.org/project/enhanced_image_formatter
[17]: https://www.drupal.org/project/svg_image
[18]: https://www.drupal.org/project/custom_formatters
[19]: https://www.drupal.org/project/linked_field
[20]: https://www.drupal.org/project/issues/image_link_formatter
