import React, { Component } from "react";
import ProductList from "./components/ProductList";
import Header from "./components/Header";
import { ApolloClient, InMemoryCache, ApolloProvider } from "@apollo/client";
import { GET_PRODUCTS } from "../graphqlqueries";
import ProductDetailsWrapper from "./components/ProductDetailsWrapper";
import { BrowserRouter, Route, Routes } from "react-router-dom";
import { CartProvider } from "../context/CartContext";
import LoaderComponent from "./components/Loader";

const client = new ApolloClient({
  uri: "http://localhost:8000/graphql",
  cache: new InMemoryCache(),
});

class App extends Component {
  state = {
    products: [],
    filteredProducts: [],
    loading: true,
    error: null,
    selectedCategory: "all",
  };

  componentDidMount() {
    client
      .query({ query: GET_PRODUCTS })
      .then((result) => {
        this.setState({
          products: result.data.products,
          filteredProducts: result.data.products,
          loading: false,
        });
      })
      .catch((error) => this.setState({ error, loading: false }));
  }

  handleCategoryChange = (category) => {
    const filteredProducts =
      category === "all"
        ? this.state.products
        : this.state.products.filter(
            (product) => product.category === category
          );
    this.setState({ filteredProducts, selectedCategory: category });
  };

  updateProducts = (newProducts) => {
    this.setState({ products: newProducts });
  };

  render() {
    const { loading, error, filteredProducts, selectedCategory } = this.state;

    if (loading) return <LoaderComponent />;
    if (error) return <p>Error: {error.message}</p>;

    return (
      <ApolloProvider client={client}>
        <CartProvider>
          <BrowserRouter>
            <Header
              onCategoryChange={this.handleCategoryChange}
              selectedCategory={selectedCategory}
            />
            <Routes>
              <Route
                path="/"
                element={
                  <ProductList
                    products={filteredProducts}
                    selectedCategory={selectedCategory}
                    updateProducts={this.updateProducts}
                  />
                }
              />
              <Route path="/product/:id" element={<ProductDetailsWrapper />} />
            </Routes>
          </BrowserRouter>
        </CartProvider>
      </ApolloProvider>
    );
  }
}

export default App;
