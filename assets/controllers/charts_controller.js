import { Controller } from "@hotwired/stimulus";
import Chart from "chart.js/auto";
import { jsPDF } from "jspdf";

export default class CharController extends Controller {
    static targets = ["evolutionChart", "motifsChart"];
    static values = {
        data: Object,
        labels: Object,
    };

    connect() {
        this.chartData = this.dataValue;
        this.motifsLabels = this.labelsValue;

        if (this.hasEvolutionChartTarget && this.chartData.evolution) {
            this.renderEvolutionChart("bar");
        }
        if (this.hasMotifsChartTarget && this.chartData.motifs) {
            this.renderMotifsChart();
        }
    }

    renderEvolutionChart(type) {
        const labels = Object.keys(this.chartData.evolution);
        const revenueData = labels.map(
            (month) => this.chartData.evolution[month].revenue
        );
        const unpaidData = labels.map(
            (month) => this.chartData.evolution[month].unpaid
        );

        if (this.evolutionChartInstance) {
            this.evolutionChartInstance.destroy();
        }

        this.evolutionChartInstance = new Chart(this.evolutionChartTarget, {
            type: type,
            data: {
                labels: labels,
                datasets: [
                    {
                        label: "Chiffre d'affaires",
                        data: revenueData,
                        backgroundColor: "rgba(75, 192, 192, 0.5)",
                        borderColor: "rgb(75, 192, 192)",
                        borderWidth: 1,
                    },
                    {
                        label: "Impayés",
                        data: unpaidData,
                        backgroundColor: "rgba(255, 99, 132, 0.5)",
                        borderColor: "rgb(255, 99, 132)",
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                    },
                },
                responsive: true,
                maintainAspectRatio: false,
            },
        });
    }

    renderMotifsChart() {
        const labels = Object.keys(this.chartData.motifs).map(
            (key) => this.motifsLabels[key] || key
        );
        const counts = Object.values(this.chartData.motifs).map((m) => m.count);

        new Chart(this.motifsChartTarget, {
            type: "pie",
            data: {
                labels: labels,
                datasets: [
                    {
                        label: "Nombre d'impayés",
                        data: counts,
                        backgroundColor: [
                            "#FF6384",
                            "#36A2EB",
                            "#FFCE56",
                            "#4BC0C0",
                            "#9966FF",
                            "#FF9F40",
                            "#C9CBCF",
                            "#E7E9ED",
                        ],
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
            },
        });
    }

    changeEvolutionChartType(event) {
        const type = event.currentTarget.dataset.chartType;
        this.renderEvolutionChart(type);

        // Toggle active class on buttons
        const buttons = event.currentTarget.parentElement.children;
        for (let i = 0; i < buttons.length; i++) {
            buttons[i].classList.remove("active");
        }
        event.currentTarget.classList.add("active");
    }

    exportPdf() {
        const doc = new jsPDF();
        const canvases = this.element.querySelectorAll("canvas");
        let y = 15;

        doc.text("Rapports sur les impayés", 105, y, { align: "center" });
        y += 10;

        canvases.forEach((canvas, index) => {
            if (index > 0) {
                doc.addPage();
                y = 15;
            }
            const imgData = canvas.toDataURL("image/png", 1.0);
            const imgProps = doc.getImageProperties(imgData);
            const pdfWidth = doc.internal.pageSize.getWidth() - 20;
            const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

            const cardHeader = canvas
                .closest(".card")
                .querySelector(".card-header");
            if (cardHeader) {
                doc.text(cardHeader.innerText, 10, y);
                y += 10;
            }

            doc.addImage(imgData, "PNG", 10, y, pdfWidth, pdfHeight);
        });

        doc.save("rapport-impayes.pdf");
    }
}
