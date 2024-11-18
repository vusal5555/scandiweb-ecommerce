import React, { Component } from "react";
import { ShoppingCart } from "lucide-react";
import { Link } from "react-router-dom";
import { CartContext } from "../../context/CartContext";
import { client } from "../main";
import { GET_PRODUCTS } from "../../graphqlqueries";
import LoaderComponent from "./Loader";

class ProductList extends Component {
  state = {
    hoveredProductId: null,
    loading: true,
    error: null,
  };

  componentDidUpdate(prevProps) {
    if (prevProps.selectedCategory !== this.props.selectedCategory) {
      this.fetchProducts();
    }
  }

  fetchProducts = () => {
    this.setState({ loading: true });
    client
      .query({
        query: GET_PRODUCTS,
        variables: { category: this.props.selectedCategory },
      })
      .then((result) => {
        this.props.updateProducts(result.data.products);
        this.setState({ loading: false });
      })
      .catch((error) => this.setState({ error, loading: false }));
  };

  componentDidMount() {
    this.fetchProducts();
  }

  handleMouseEnter = (id) => this.setState({ hoveredProductId: id });
  handleMouseLeave = () => this.setState({ hoveredProductId: null });

  addToCart = (product, addToCart) => {
    const productCopy = { ...product, quantity: 1 };

    if (product.attributes && product.attributes.length > 0) {
      productCopy.selectedAttributes = product.attributes.reduce(
        (acc, attribute) => {
          acc[attribute.name] = attribute.items[0].id;
          return acc;
        },
        {}
      );
    }

    addToCart(productCopy);
  };

  render() {
    const { products, selectedCategory } = this.props;
    const { hoveredProductId, loading, error } = this.state;

    if (loading) return <LoaderComponent />;
    if (error) return <p>Error: {error.message}</p>;

    return (
      <CartContext.Consumer>
        {({ addToCart }) => (
          <div className="bg-slate-200">
            <div className="max-w-7xl mx-auto pt-20 px-5 pb-5">
              <h1 className="mb-32 text-4xl">
                {selectedCategory === "all"
                  ? "Product List"
                  : selectedCategory.charAt(0).toUpperCase() +
                    selectedCategory.slice(1)}
              </h1>
              <div className="grid md:grid-cols-3 items-center justify-between gap-10">
                {products.map((product) => (
                  <Link
                    data-testid={`product-${product.name
                      .toLowerCase()
                      .replace(/ /g, "-")}`}
                    key={product.id}
                    to={`/product/${product.id}`}
                    className="flex flex-col gap-1 cursor-pointer p-5 relative"
                    onMouseEnter={() => this.handleMouseEnter(product.id)}
                    onMouseLeave={this.handleMouseLeave}
                  >
                    <img
                      src={product.gallery[0]}
                      className={`w-full h-full object-cover transition ${
                        !product.in_stock ? "opacity-50 grayscale" : ""
                      }`}
                      alt={product.name}
                    />
                    {!product.in_stock && (
                      <div className="absolute inset-0 bg-black/20 flex items-center justify-center">
                        <span className="text-white text-2xl font-semibold uppercase">
                          Out of Stock
                        </span>
                      </div>
                    )}
                    <h2 className="text-gray-400">{product.name}</h2>
                    <p>
                      {product.prices.map(
                        (price) =>
                          `${price.currency.symbol}${price.amount.toFixed(2)}`
                      )}
                    </p>
                    {product.in_stock && hoveredProductId === product.id && (
                      <button
                        className="absolute bottom-2 right-2 bg-green-500 rounded-full text-white px-3 py-3"
                        onClick={(e) => {
                          e.preventDefault();
                          this.addToCart(product, addToCart);
                        }}
                      >
                        <ShoppingCart size={16} />
                      </button>
                    )}
                  </Link>
                ))}
              </div>
            </div>
          </div>
        )}
      </CartContext.Consumer>
    );
  }
}

export default ProductList;
