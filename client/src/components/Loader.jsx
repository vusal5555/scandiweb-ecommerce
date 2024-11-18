import { Loader } from "lucide-react";
import React, { Component } from "react";

class LoaderComponent extends Component {
  render() {
    return (
      <div className="flex items-center justify-center h-screen">
        <Loader className="animate-spin w-7 h-7"></Loader>
      </div>
    );
  }
}

export default LoaderComponent;
