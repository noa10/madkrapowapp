import React from 'react';
import { Link } from 'react-router-dom';
import { Check, Star, Shield } from 'lucide-react';
import { Button } from '@/components/ui/button';
import Navbar from '@/components/Navbar';
import Footer from '@/components/Footer';

const About: React.FC = () => {
  return (
    <div className="min-h-screen flex flex-col">
      <Navbar />
      
      {/* Hero Section */}
      <section className="bg-gray-100 py-20">
        <div className="container mx-auto px-4 text-center">
          <h1 className="text-4xl md:text-5xl font-bold mb-4 text-gray-800">Crafting Authentic Thai Flavors</h1>
          <p className="text-xl text-gray-600 max-w-2xl mx-auto">Where tradition meets innovation in every bite.</p>
        </div>
      </section>
      
      {/* Our Story Section */}
      <section className="py-16">
        <div className="container mx-auto px-4">
          <h2 className="text-3xl font-bold mb-10 text-center">Our Story</h2>
          <div className="max-w-3xl mx-auto">
            <p className="text-gray-700 mb-6">
              Founded in 2023, Mad Krapow brings the vibrant and authentic flavors of Thai cuisine directly to your doorstep in Shah Alam, Selangor, and across Malaysia. What began as a passionate endeavor to share cherished family recipes has quickly blossomed into a beloved culinary destination.
            </p>
            <p className="text-gray-700 mb-6">
              Our journey started with a simple desire: to make the rich and diverse tastes of Thailand accessible to everyone. We believe that great food should be made with love, using only the freshest ingredients and time-honored cooking techniques. This commitment to quality and authenticity is at the heart of everything we do at Mad Krapow.
            </p>
            <p className="text-gray-700">
              We're more than just a food delivery service; we're a culinary bridge, connecting you to the heart of Thai gastronomy. From our aromatic ready-to-cook pastes to our delectable Thai dishes, each product is crafted with meticulous attention to detail and a deep respect for tradition.
            </p>
          </div>
        </div>
      </section>
      
      {/* Core Values Section */}
      <section className="py-16 bg-gray-50">
        <div className="container mx-auto px-4">
          <h2 className="text-3xl font-bold mb-12 text-center">Our Core Values</h2>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {/* Fresh Ingredients */}
            <div className="bg-white p-8 rounded-lg shadow-md text-center">
              <div className="flex justify-center mb-6">
                <div className="w-16 h-16 bg-thaired rounded-full flex items-center justify-center">
                  <Check className="h-8 w-8 text-white" />
                </div>
              </div>
              <h3 className="text-xl font-bold mb-4">Fresh Ingredients</h3>
              <p className="text-gray-600">
                Daily-sourced local produce and premium meats curated by our master chefs.
              </p>
            </div>
            
            {/* Authentic Recipes */}
            <div className="bg-white p-8 rounded-lg shadow-md text-center">
              <div className="flex justify-center mb-6">
                <div className="w-16 h-16 bg-thaired rounded-full flex items-center justify-center">
                  <Star className="h-8 w-8 text-white" />
                </div>
              </div>
              <h3 className="text-xl font-bold mb-4">Authentic Recipes</h3>
              <p className="text-gray-600">
                Centuries-old family recipes preserved through generations of Thai culinary tradition.
              </p>
            </div>
            
            {/* Sustainable Sourcing */}
            <div className="bg-white p-8 rounded-lg shadow-md text-center">
              <div className="flex justify-center mb-6">
                <div className="w-16 h-16 bg-thaired rounded-full flex items-center justify-center">
                  <Shield className="h-8 w-8 text-white" />
                </div>
              </div>
              <h3 className="text-xl font-bold mb-4">Sustainable Sourcing</h3>
              <p className="text-gray-600">
                Ethically sourced ingredients supporting local farmers and communities.
              </p>
            </div>
          </div>
        </div>
      </section>
      
      {/* Meet Our Chefs Section */}
      <section className="py-16">
        <div className="container mx-auto px-4">
          <h2 className="text-3xl font-bold mb-4 text-center">Meet Our Chefs</h2>
          <p className="text-xl text-gray-600 text-center mb-12">The culinary experts behind our authentic flavors</p>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {/* Head Chef */}
            <div className="bg-white p-6 rounded-lg shadow-md text-center">
              <div className="w-32 h-32 mx-auto mb-6 rounded-full overflow-hidden bg-gray-200">
                <img 
                  src="https://images.unsplash.com/photo-1577219491135-ce391730fb2c?ixlib=rb-1.2.1&auto=format&fit=crop&w=300&q=80" 
                  alt="Head Chef" 
                  className="w-full h-full object-cover"
                />
              </div>
              <h3 className="text-xl font-bold">Somchai P.</h3>
              <p className="text-gray-600">Master Chef</p>
            </div>
            
            {/* Sous Chef */}
            <div className="bg-white p-6 rounded-lg shadow-md text-center">
              <div className="w-32 h-32 mx-auto mb-6 rounded-full overflow-hidden bg-gray-200">
                <img 
                  src="https://images.unsplash.com/photo-1581299894007-aaa50297cf16?ixlib=rb-1.2.1&auto=format&fit=crop&w=300&q=80" 
                  alt="Sous Chef" 
                  className="w-full h-full object-cover"
                />
              </div>
              <h3 className="text-xl font-bold">Nonglak S.</h3>
              <p className="text-gray-600">Sous Chef</p>
            </div>
            
            {/* Pastry Chef */}
            <div className="bg-white p-6 rounded-lg shadow-md text-center">
              <div className="w-32 h-32 mx-auto mb-6 rounded-full overflow-hidden bg-gray-200">
                <img 
                  src="https://images.unsplash.com/photo-1607631568010-a87245c0daf8?ixlib=rb-1.2.1&auto=format&fit=crop&w=300&q=80" 
                  alt="Pastry Chef" 
                  className="w-full h-full object-cover"
                />
              </div>
              <h3 className="text-xl font-bold">Pimchanok W.</h3>
              <p className="text-gray-600">Pastry Chef</p>
            </div>
          </div>
        </div>
      </section>
      
      {/* Questions Section */}
      <section className="py-12 bg-red-50">
        <div className="container mx-auto px-4 text-center">
          <h2 className="text-2xl font-bold mb-6">Have Questions?</h2>
          <Link to="/contact">
            <Button className="bg-thaired hover:bg-thaired-dark text-white px-8 py-2">
              Contact Us
            </Button>
          </Link>
        </div>
      </section>
      
      <Footer />
    </div>
  );
};

export default About;
