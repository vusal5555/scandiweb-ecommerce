import React, { Component } from "react";
import { ShoppingCart } from "lucide-react";
import { Link, NavLink, useNavigate } from "react-router-dom";
import { CartContext } from "../../context/CartContext";
import { client } from "../main";
import { GET_CATEGORIES } from "../../graphqlqueries";
import CartOverlay from "./CartOverlay";
import LoaderComponent from "./Loader";

class Header extends Component {
  static contextType = CartContext;

  state = {
    categories: [],
    loading: true,
    error: null,
    selectedCategory: "all",
  };

  componentDidMount() {
    client
      .query({ query: GET_CATEGORIES })
      .then((result) => {
        this.setState({
          categories: result.data.categories,
          loading: false,
        });
      })
      .catch((error) => this.setState({ error, loading: false }));
  }

  componentDidUpdate(_, prevState) {
    const { isOverlayOpen } = this.context;

    if (isOverlayOpen !== prevState.isOverlayOpen) {
      document.body.style.overflow = isOverlayOpen ? "hidden" : "auto";
    }
  }

  componentWillUnmount() {
    document.body.style.overflow = "auto";
  }

  handleCategoryChange = (category, navigate) => {
    this.setState({ selectedCategory: category });
    this.props.onCategoryChange(category);
    navigate("/");
  };

  setDataId = (category) => {
    return category === this.state.selectedCategory
      ? "active-category-link"
      : "category-link";
  };

  render() {
    const { categories, selectedCategory, loading, error } = this.state;
    const { cartItems, isOverlayOpen, openOverlay, closeOverlay } =
      this.context;

    if (loading) return <LoaderComponent />;
    if (error) return <p>Error loading categories: {error.message}</p>;

    return (
      <div className="relative">
        {isOverlayOpen && (
          <div className="fixed top-0 left-0 right-0 bottom-0 bg-gray-500 opacity-50 z-10 mt-20" />
        )}

        <div className="max-w-7xl mx-auto px-5 py-5 relative z-20">
          <div className="flex items-center justify-between">
            <CategoryMenu
              categories={categories}
              selectedCategory={selectedCategory}
              onCategoryChange={this.handleCategoryChange}
              setDataId={this.setDataId}
            />

            <Link to="/">
              <img src="/a-logo.png" alt="Logo" className="h-10" />
            </Link>

            <div className="relative">
              <button data-testid="cart-btn" onClick={openOverlay}>
                <ShoppingCart size={27} />
              </button>

              {cartItems.length > 0 && (
                <div className="absolute top-0 right-0 bg-green-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                  {cartItems.reduce((total, item) => total + item.quantity, 0)}{" "}
                </div>
              )}

              {isOverlayOpen && (
                <div
                  className="absolute top-full mt-2 right-0 w-max bg-white shadow-lg rounded-lg z-[999] "
                  style={{ left: "auto" }}
                >
                  <CartOverlay closeOverlay={closeOverlay} />
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    );
  }
}

const CategoryMenu = ({
  categories,
  selectedCategory,
  onCategoryChange,
  setDataId,
}) => {
  const navigate = useNavigate();

  return (
    <ul className="flex items-center gap-7 uppercase">
      {categories.map((category) => (
        <li key={category.id}>
          <NavLink
            data-testid={setDataId(category.name)}
            to="/"
            className={({ isActive }) =>
              `header-link ${
                selectedCategory === category.name ? "active" : ""
              }`
            }
            onClick={(e) => {
              e.preventDefault();
              onCategoryChange(category.name, navigate);
            }}
          >
            {category.name}
          </NavLink>
        </li>
      ))}
    </ul>
  );
};

export default Header;
