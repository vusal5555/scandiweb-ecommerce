import React, { Component } from "react";
import { client } from "../main";
import ReactMarkdown from "react-markdown";
import rehypeRaw from "rehype-raw";
import { GET_PRODUCT_DETAILS } from "../../graphqlqueries";
import LoaderComponent from "./Loader";

class ProductDetails extends Component {
  state = {
    product: null,
    loading: true,
    error: null,
    selectedAttributes: {},
    currentImageIndex: 0,
  };

  componentDidMount() {
    const { id } = this.props;

    client
      .query({
        query: GET_PRODUCT_DETAILS,
        variables: { id },
      })
      .then((result) => {
        this.setState({ product: result.data.product, loading: false });
      })
      .catch((error) => this.setState({ error, loading: false }));
  }

  handleAttributeSelect = (attributeId, itemId) => {
    this.setState((prevState) => ({
      selectedAttributes: {
        ...prevState.selectedAttributes,
        [attributeId]: itemId,
      },
    }));
  };

  handleAddToCart = () => {
    const { product, selectedAttributes } = this.state;

    const normalizedSelectedAttributes = product.attributes.reduce(
      (acc, attribute) => {
        acc[attribute.name] = selectedAttributes[attribute.id];
        return acc;
      },
      {}
    );

    const cartItem = {
      __typename: product.__typename || "Product",
      id: product.id,
      name: product.name,
      in_stock: product.in_stock,
      gallery: product.gallery,
      description: product.description,
      category: product.category,
      brand: product.brand,
      prices: product.prices,
      attributes: product.attributes,
      selectedAttributes: normalizedSelectedAttributes,
      quantity: 1,
    };

    const cartItems = JSON.parse(localStorage.getItem("cartItems")) || [];

    const existingProductIndex = cartItems.findIndex(
      (item) =>
        item.id === product.id &&
        JSON.stringify(item.selectedAttributes) ===
          JSON.stringify(normalizedSelectedAttributes)
    );

    if (existingProductIndex >= 0) {
      cartItems[existingProductIndex].quantity += 1;
    } else {
      cartItems.push(cartItem);
    }

    localStorage.setItem("cartItems", JSON.stringify(cartItems));

    this.props.onAddToCart(cartItem);
  };

  handleImageClick = (index) => {
    this.setState({ currentImageIndex: index });
  };

  handleNextImage = () => {
    this.setState((prevState) => ({
      currentImageIndex:
        (prevState.currentImageIndex + 1) % prevState.product.gallery.length,
    }));
  };

  handlePrevImage = () => {
    this.setState((prevState) => ({
      currentImageIndex:
        (prevState.currentImageIndex - 1 + prevState.product.gallery.length) %
        prevState.product.gallery.length,
    }));
  };

  render() {
    const { product, loading, error, selectedAttributes, currentImageIndex } =
      this.state;

    if (loading) return <LoaderComponent />;
    if (error) return <p>Error: {error.message}</p>;

    const cartItems = JSON.parse(localStorage.getItem("cartItems")) || [];

    const isAddToCartEnabled =
      product.in_stock &&
      product.attributes.every((attr) => selectedAttributes[attr.id]);

    return (
      <div className="grid lg:grid-cols-2 items-start gap-8 max-w-7xl mx-auto py-10 px-3 mt-10">
        {/* Product Gallery */}
        <div
          className="flex flex-col lg:flex-row"
          data-testid="product-gallery"
        >
          {/* Left-hand Side Gallery */}
          <div
            className="flex flex-row lg:flex-col space-y-2 mr-4 max-h-[500px] overflow-y-auto"
            data-testid="product-gallery"
          >
            {product.gallery.map((image, index) => (
              <img
                key={index}
                src={image}
                className={`w-16 h-16 cursor-pointer object-cover ${
                  index === currentImageIndex
                    ? "border-2 border-green-500"
                    : "border"
                }`}
                onClick={() => this.handleImageClick(index)}
                alt={`Thumbnail ${index + 1}`}
              />
            ))}
          </div>

          {/* Main Image with Navigation Arrows */}
          <div className="relative flex-1">
            <img
              src={product.gallery[currentImageIndex]}
              className="w-full h-full object-cover max-h-[500px] overflow-hidden"
              alt={product.name}
            />

            {/* Left Arrow */}
            <button
              onClick={this.handlePrevImage}
              className="absolute left-2 top-1/2 transform -translate-y-1/2 bg-gray-700 text-white p-2 py-1 rounded-sm z-10"
            >
              &lt;
            </button>

            {/* Right Arrow */}
            <button
              onClick={this.handleNextImage}
              className="absolute right-2 top-1/2 transform -translate-y-1/2 bg-gray-700 text-white p-2 py-1 rounded-sm z-10"
            >
              &gt;
            </button>
          </div>
        </div>

        {/* Product Details */}
        <div className="space-y-6">
          <h1 className="text-3xl font-bold">{product.name}</h1>

          {/* Attributes */}
          {product.attributes.map((attribute) => (
            <div
              key={attribute.id}
              className="mt-4"
              data-testid={`product-attribute-${attribute.name
                .toLowerCase()
                .replace(/ /g, "-")}`}
            >
              <h3 className="text-xl font-semibold">{attribute.name}</h3>
              <div className="flex space-x-2 mt-2">
                {attribute.items.map((item) => (
                  <button
                    key={item.id}
                    className={`p-2 rounded border ${
                      selectedAttributes[attribute.id] === item.id
                        ? "bg-green-500 text-white border-1 border-gray-600" // Highlighted border for selected item
                        : "bg-gray-200"
                    }`}
                    style={{
                      backgroundColor:
                        attribute.name.toLowerCase() === "color"
                          ? item.value
                          : undefined,
                    }}
                    onClick={() =>
                      this.handleAttributeSelect(attribute.id, item.id)
                    }
                  >
                    {attribute.name.toLowerCase() !== "color" &&
                      item.displayValue}
                  </button>
                ))}
              </div>
            </div>
          ))}

          {/* Price */}
          <p className="text-2xl font-semibold">
            {product.prices[0].currency.symbol}
            {product.prices[0].amount.toFixed(2)}
          </p>

          {/* Add to Cart Button */}
          <button
            onClick={this.handleAddToCart}
            disabled={!isAddToCartEnabled}
            data-testid="add-to-cart"
            className={`px-6 py-3 rounded bg-green-500 text-white font-bold ${
              !isAddToCartEnabled && "bg-green-500/60 cursor-not-allowed"
            }`}
          >
            Add to Cart
          </button>

          {/* Description */}
          <div className="mt-4 text-gray-700">
            <ReactMarkdown
              data-testid="product-description"
              rehypePlugins={[rehypeRaw]}
            >
              {product.description}
            </ReactMarkdown>
          </div>
        </div>
      </div>
    );
  }
}

export default ProductDetails;
