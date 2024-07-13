/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './templates/**/*.twig', // Chemin vers vos templates Twig
    './src/**/*.php',        // Chemin vers vos fichiers PHP Symfony
    // Ajoutez d'autres chemins selon les besoins
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}

