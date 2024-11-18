import React, { Component } from "react";
import { client } from "../main";
import { CREATE_ORDER } from "../../graphqlqueries";
import { CartContext } from "../../context/CartContext";

class CartOverlay extends Component {
  calculateTotal = () => {
    const { cartItems } = this.context; // Access cartItems from context
    return cartItems
      .reduce((total, item) => {
        let price;
        if (item.__typename) {
          price = parseFloat(item.prices[0].amount);
        } else {
          price = parseFloat(item.price);
        }

        const quantity = item.quantity;

        if (isNaN(price) || isNaN(quantity)) {
          console.warn("Invalid price or quantity for item:", item);
          return total;
        }

        return total + Number(price) * Number(quantity);
      }, 0)
      .toFixed(2);
  };

  placeOrder = () => {
    const { cartItems, updateCartItems } = this.context;

    const products = cartItems.map((item) => ({
      id: String(item.id),
      quantity: item.quantity,
      attributes: item.selectedAttributes
        ? JSON.stringify(item.selectedAttributes)
        : null, // Use null when attributes are not present
    }));

    client
      .mutate({
        mutation: CREATE_ORDER,
        variables: { products },
      })
      .then((result) => {
        const { success, message } = result.data.createOrder;

        if (success) {
          alert(message);
          updateCartItems([]);
        } else {
          alert(`Order Failed: ${message}`);
        }
      })
      .catch((error) => {
        console.error("Error placing order:", error);
        alert("An error occurred while placing your order.");
      });
  };

  handleQuantityChange = (item, type) => {
    const { cartItems, updateCartItems } = this.context; // Access cartItems and updateCartItems from context

    const updatedItems = cartItems
      .map((cartItem) => {
        if (
          cartItem.id === item.id &&
          JSON.stringify(cartItem.attributes) ===
            JSON.stringify(item.attributes)
        ) {
          if (type === "increase") {
            return { ...cartItem, quantity: cartItem.quantity + 1 };
          } else if (type === "decrease") {
            if (cartItem.quantity === 1) {
              // Remove item if quantity is 1
              return null;
            } else {
              return { ...cartItem, quantity: cartItem.quantity - 1 };
            }
          }
        }
        return cartItem;
      })
      .filter((item) => item !== null); // Filter out null items (deleted items)

    updateCartItems(updatedItems); // Update the cart via context
  };

  render() {
    const { cartItems, closeOverlay } = this.context; // Access cartItems and closeOverlay from context

    const itemCount = cartItems
      ? cartItems.reduce((total, item) => total + item.quantity, 0)
      : 0;

    return (
      <div className="bg-white p-5 rounded-lg shadow-md w-full lg:w-[500px]  h-full overflow-y-auto">
        <button onClick={closeOverlay} className="float-right">
          X
        </button>
        <h2 className="text-2xl mb-4" data-testid="cart-item-amount">
          {itemCount === 1 ? "1 Item" : `${itemCount} Items`}
        </h2>

        {/* Product List */}
        {cartItems && cartItems.length > 0 ? (
          <ul className="max-h-[70vh] overflow-y-auto">
            {cartItems.map((item, index) => (
              <div
                key={index}
                data-testid={`cart-item-attribute-${item.name
                  .toLowerCase()
                  .replace(/ /g, "-")}}`}
              >
                <li
                  className="flex items-center mb-4"
                  data-testid={`cart-item-attribute-${item.name
                    .toLowerCase()
                    .replace(/ /g, "-")}}-${item.name
                    .toLowerCase()
                    .replace(/ /g, "-")}}`}
                >
                  <img
                    src={
                      item.gallery && item.gallery.length > 0
                        ? item.gallery[0]
                        : ""
                    }
                    alt={item.name}
                    className="w-16 h-16 object-contain"
                  />
                  <div className="ml-4">
                    <h3 className="font-extrabold">{item.name}</h3>

                    {/* Display selected options */}
                    {item.__typename
                      ? item.attributes &&
                        item.attributes.map((attribute, attrIndex) => (
                          <div
                            key={attrIndex}
                            className="mt-2 flex flex-col gap-1"
                          >
                            <p className="font-semibold">{attribute.name}:</p>
                            <div className="flex gap-2">
                              {attribute.items.map((option, optionIndex) => {
                                const isSelected =
                                  item.selectedAttributes &&
                                  item.selectedAttributes[attribute.name] ===
                                    option.id;
                                return (
                                  <div
                                    data-testid={`cart-item-attribute-${attribute.name
                                      .toLowerCase()
                                      .replace(/ /g, "-")}-${attribute.name
                                      .toLowerCase()
                                      .replace(/ /g, "-")}-selected`}
                                    key={optionIndex}
                                    className={`p-2 border rounded ${
                                      isSelected
                                        ? "bg-green-300 border-green-500"
                                        : "bg-gray-100"
                                    }`}
                                  >
                                    <span>{option.displayValue}</span>
                                  </div>
                                );
                              })}
                            </div>
                          </div>
                        ))
                      : item.attributes &&
                        Object.keys(item.attributes).map(
                          (attributeKey, attrIndex) => (
                            <div key={attrIndex} className="mt-2">
                              <div className="p-2 border rounded bg-gray-100">
                                <span>{item.attributes[attributeKey]}</span>
                              </div>
                            </div>
                          )
                        )}

                    {/* Quantity Controls */}
                    <div className="flex items-center gap-2">
                      <button
                        data-testid="cart-item-amount-increase"
                        onClick={() =>
                          this.handleQuantityChange(item, "increase")
                        }
                      >
                        +
                      </button>
                      <span>{item.quantity}</span>
                      <button
                        data-testid="cart-item-amount-decrease"
                        onClick={() =>
                          this.handleQuantityChange(item, "decrease")
                        }
                      >
                        -
                      </button>
                    </div>
                  </div>
                </li>
              </div>
            ))}
          </ul>
        ) : (
          <p>No items in cart</p>
        )}

        {/* Cart Total */}
        <div className="mt-4">
          <p data-testid="cart-total">Total: ${this.calculateTotal()}</p>
          <button
            onClick={this.placeOrder}
            disabled={cartItems.length === 0}
            className={`mt-4 p-2 w-full ${
              cartItems.length === 0 ? "bg-gray-400" : "bg-green-500"
            }`}
          >
            Place Order
          </button>
        </div>
      </div>
    );
  }
}

// Attach CartContext to the component
CartOverlay.contextType = CartContext;

export default CartOverlay;
