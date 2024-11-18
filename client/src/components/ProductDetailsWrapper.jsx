import React, { useContext } from "react";
import { useParams } from "react-router-dom";
import ProductDetails from "./ProductDetails";
import { CartContext } from "../../context/CartContext";

const ProductDetailsWrapper = () => {
  //Hand to use useParams hook to get the id from the URL, this is the only case where we need to use the hook to get the id from the URL as the latest version of react-router-dom does not suppot the withRouter HOC
  const { id } = useParams();

  const { addToCart, openOverlay } = useContext(CartContext);

  const handleAddToCart = (item) => {
    addToCart(item);
    openOverlay();
  };
  return <ProductDetails id={id} onAddToCart={handleAddToCart} />;
};

export default ProductDetailsWrapper;
