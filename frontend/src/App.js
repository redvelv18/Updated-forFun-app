import React, { useState, useEffect } from "react";
import axios from "axios";
import "./App.css";

function App() {
  const [movies, setMovies] = useState([]);
  const [formData, setFormData] = useState({
    title: "",
    author: "",
    rating: 5,
    notes: "",
    image: null,
  });

  useEffect(() => {
    fetchMovies();
  }, []);


  const fetchMovies = async () => {
    const res = await axios.get("http://localhost:8000/api/movies");
    setMovies(res.data);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const data = new FormData();
    Object.keys(formData).forEach((key) => data.append(key, formData[key]));

    await axios.post("http://localhost:8000/api/movies", data);
    setFormData({ title: "", author: "", rating: 5, notes: "", image: null });
    fetchMovies();
  };

  return (
    <div className="page-wrapper">
    <div className="main-container">
    <div className="App">
      <h1>My Movie Journal</h1>

      <form onSubmit={handleSubmit} className="movie-form">
        <input
          type="text"
          placeholder="Movie Title"
          required
          onChange={(e) => setFormData({ ...formData, title: e.target.value })}
        />
        <br></br>
        <input
          type="text"
          placeholder="Director/Author"
          required
          onChange={(e) => setFormData({ ...formData, author: e.target.value })}
        />
        <br></br>
        <textarea
          placeholder="Notes"
          onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
        />
        <br></br>
        <input
          type="number"
          min="1"
          max="5"
          value={formData.rating}
          onChange={(e) => setFormData({ ...formData, rating: e.target.value })}
        />
        <br></br>
        <input
          type="file"
          onChange={(e) =>
            setFormData({ ...formData, image: e.target.files[0] })
          }
        />
        <br></br>
        <button type="submit">Save Movie</button>
      </form>
      <br></br>

      <div className="movie-grid">
        {movies.map((movie) => (
          <div key={movie.id} className="movie-card">
            <img
              src={`http://localhost:8000/${movie.image_path}`}
              alt={movie.title}
            />
            <h3>{movie.title}</h3>
            <p>Directed by: {movie.author}</p>
            <p className="stars">{"★".repeat(movie.rating)}</p>
            <p>{movie.notes}</p>
          </div>
        ))}
      </div>
    </div>
    </div>
  </div>
  );
}

export default App;
