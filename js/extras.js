document.getElementById("myNumberInput").addEventListener("keydown", function(event) {
  if (["e", "E", "+", "-"].includes(event.key)) {
    event.preventDefault(); // Prevents the character from being entered
  }
});