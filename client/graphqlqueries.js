import { gql } from "@apollo/client";

export const GET_PRODUCTS = gql`
  query GetProducts {
    products {
      id
      name
      in_stock
      gallery
      description
      category
      brand
      prices {
        amount
        currency {
          label
          symbol
        }
      }
      attributes {
        id
        name
        items {
          id
          displayValue
          value
        }
      }
    }
  }
`;

export const GET_PRODUCT_DETAILS = gql`
  query GetProduct($id: ID!) {
    product(id: $id) {
      id
      name
      in_stock
      gallery
      description
      category
      brand
      attributes {
        id
        name
        items {
          displayValue
          value
          id
        }
      }
      prices {
        amount
        currency {
          label
          symbol
        }
      }
    }
  }
`;

export const GET_CATEGORIES = gql`
  query GetCategories {
    categories {
      id
      name
    }
  }
`;

export const CREATE_ORDER = gql`
  mutation CreateOrder($products: [ProductInput!]!) {
    createOrder(products: $products) {
      success
      message
    }
  }
`;
