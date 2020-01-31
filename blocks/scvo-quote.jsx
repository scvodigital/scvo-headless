

wp.blocks.registerBlockType('scvo/quote', {
  title: 'Quote',
  icon: 'quote',
  category: 'common',
  attributes: {
    content: {type: 'string'}
  },

/* This configures how the content and color fields will work, and sets up the necessary elements */

  edit: function(props) {
    function updateContent(event) {
      props.setAttributes({content: event.target.value})
    }
    return React.createElement(
      "div",
      null,
      React.createElement(
        "h3",
        null,
        "Simple Box"
      ),
      React.createElement("input", { type: "text", value: props.attributes.content, onChange: updateContent })
    );
  },
  save: function(props) {
    return wp.element.createElement(
      "p",
      null,
      props.attributes.content
    );
  }
})